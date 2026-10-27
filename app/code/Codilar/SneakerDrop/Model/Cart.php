<?php
declare(strict_types=1);

namespace Codilar\SneakerDrop\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\ResourceModel\Cart as CartResource;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Replaces Magento's storefront cart model (see etc/frontend/di.xml).
 *
 * 1. Limit check: for limited products,
 *      already ordered + already in cart + this add  <=  max_per_customer
 *    otherwise "This is a limited release..." and nothing is added.
 *
 * 2. Replaces the core PRIVATE getQtyRequest() (vendor/magento/module-checkout/Model/Cart.php).
 *    A private method cannot be overridden, so the PUBLIC addProduct() that calls
 *    it is overridden with a copy of the core code that calls OUR private
 *    getQtyRequest() (same name as the core one). parent::addProduct() is NOT
 *    called, because the core addProduct() would still run the core private version.
 *
 *    Change: core only fills in a missing qty ("Minimum Qty Allowed"). For
 *    limited products ours also lowers the qty to what the customer may still
 *    buy (limit 3, ordered 2, asks for 5 -> 1 is added, with a notice).
 *    With nothing left, the add is still blocked.
 */
class Cart extends \Magento\Checkout\Model\Cart
{
    private PurchaseLimit $purchaseLimit;

    /**
     * Same arguments as the core class, plus PurchaseLimit.
     */
    public function __construct(
        EventManager $eventManager,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CartResource $resourceCart,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        MessageManager $messageManager,
        StockRegistryInterface $stockRegistry,
        StockStateInterface $stockState,
        CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        PurchaseLimit $purchaseLimit,
        array $data = []
    ) {
        parent::__construct(
            $eventManager,
            $scopeConfig,
            $storeManager,
            $resourceCart,
            $checkoutSession,
            $customerSession,
            $messageManager,
            $stockRegistry,
            $stockState,
            $quoteRepository,
            $productRepository,
            $data
        );
        $this->purchaseLimit = $purchaseLimit;
    }

    /**
     * @inheritdoc
     */
    public function addProduct($productInfo, $requestInfo = null)
    {
        $product = $this->_getProduct($productInfo);
        $productId = $product->getId();

        if (!$productId) {
            throw new LocalizedException(__('The product does not exist.'));
        }

        $limit = $this->purchaseLimit->getLimit($product);
        $remaining = $limit > 0 ? $this->getRemainingAllowance($product, $limit) : null;

        // Calls OUR private getQtyRequest() below, not the core one.
        $request = $this->getQtyRequest($product, $requestInfo, $remaining);

        // --- From here: the core Cart::addProduct() code, unchanged ---
        try {
            $this->_eventManager->dispatch(
                'checkout_cart_product_add_before',
                ['info' => $requestInfo, 'product' => $product]
            );
            $result = $this->getQuote()->addProduct($product, $request);
        } catch (LocalizedException $e) {
            $this->_checkoutSession->setUseNotice(false);
            $result = $e->getMessage();
        }

        // String we can get if prepare process has error
        if (is_string($result)) {
            if ($product->hasOptionsValidationFail()) {
                $redirectUrl = $product->getUrlModel()->getUrl(
                    $product,
                    ['_query' => ['startcustomization' => 1]]
                );
            } else {
                $redirectUrl = $product->getProductUrl();
            }
            $this->_checkoutSession->setRedirectUrl($redirectUrl);
            if ($this->_checkoutSession->getUseNotice() === null) {
                $this->_checkoutSession->setUseNotice(true);
            }
            throw new LocalizedException(__($result));
        }

        $this->_eventManager->dispatch(
            'checkout_cart_product_add_after',
            ['quote_item' => $result, 'product' => $product]
        );
        $this->_checkoutSession->setLastAddedProductId($productId);

        return $this;
    }

    /**
     * Copy of the core Cart::addProductsByIds() (adds ticked "related products"),
     * calling OUR private getQtyRequest() instead of the core private one.
     * Limited products the customer can't buy any more of are
     * skipped with the limited-release message; the rest are added as before.
     *
     * @inheritdoc
     */
    public function addProductsByIds($productIds)
    {
        $allAvailable = true;
        $allAdded = true;

        if (!empty($productIds)) {
            foreach ($productIds as $productId) {
                $productId = (int)$productId;
                if (!$productId) {
                    continue;
                }
                $product = $this->_getProduct($productId);
                if ($product->getId() && $product->isVisibleInCatalog()) {
                    try {
                        $limit = $this->purchaseLimit->getLimit($product);
                        $remaining = $limit > 0 ? $this->getRemainingAllowance($product, $limit) : null;
                    } catch (LocalizedException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                        continue;
                    }

                    // Core: $this->getQtyRequest($product); here it resolves to OUR private method.
                    $request = $this->getQtyRequest($product, null, $remaining);
                    try {
                        $this->getQuote()->addProduct($product, $request);
                    } catch (\Exception $e) {
                        $allAdded = false;
                    }
                } else {
                    $allAvailable = false;
                }
            }

            if (!$allAvailable) {
                $this->messageManager->addErrorMessage(__("We don't have some of the products you want."));
            }
            if (!$allAdded) {
                $this->messageManager->addErrorMessage(__("We don't have as many of some products as you want."));
            }
        }

        return $this;
    }

    /**
     * OUR getQtyRequest(), replacing the core PRIVATE Cart::getQtyRequest().
     *
     * Same name as the core method, but private methods are not inherited: this
     * is a separate method. Inside this class, $this->getQtyRequest() calls
     * this one; inside the core class it would still call the core one (which
     * is why addProduct() and addProductsByIds() are overridden as well).
     *
     * Core (vendor): turn $requestInfo into a request; if it has no qty, use the
     * product's "Minimum Qty Allowed in Shopping Cart".
     *
     * Ours: the same, plus for limited products ($remaining !== null) the qty is
     * lowered to what the customer may still buy, with a notice to the customer.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param DataObject|int|array|null $requestInfo
     * @param float|null $remaining Units still allowed, or null for normal products
     * @return DataObject
     */
    private function getQtyRequest($product, $requestInfo = 0, ?float $remaining = null): DataObject
    {

        // --- core getQtyRequest() logic ---
        $request = $this->_getProductRequest($requestInfo ?? 0);
        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $minimumQty = $stockItem->getMinSaleQty();
        if ($minimumQty && $minimumQty > 0 && !$request->getQty()) {
            $request->setQty($minimumQty);
        }

        // --- our change ---
        if ($remaining !== null && (float)$request->getQty() > $remaining) {
            $request->setQty($remaining);
            $this->messageManager->addNoticeMessage(__(
                'Limited release: you can buy only %1 more, so we added %1.',
                $remaining
            ));
        }

        return $request;
    }

    /**
     * Units the customer may still add: limit - (already ordered + in cart).
     * Throws when the customer is a guest or has nothing left.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $limit
     * @return float
     * @throws LocalizedException
     */
    // private function getRemainingAllowance($product, int $limit): float
    // {
    //     $customerId = (int)($this->_customerSession->getCustomerId() ?: $this->getQuote()->getCustomerId());
    //     if (!$customerId) {
    //         throw new LocalizedException(__('Please sign in to buy this limited release.'));
    //     }

    //     $alreadyHave = $this->purchaseLimit->getOrderedQty($customerId, (int)$product->getId())
    //         + $this->purchaseLimit->getQtyInQuote($this->getQuote(), (int)$product->getId());

    //     if ($alreadyHave >= $limit) {
    //         throw new LocalizedException($this->purchaseLimit->message($limit, $alreadyHave));
    //     }

    //     return $limit - $alreadyHave;
    // }
}
