<?php
declare(strict_types=1);

namespace Codilar\CartQuantityPlugin\Plugin;

use Codilar\CartQuantityPlugin\Model\QtyLimit;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Checkout\Model\Cart;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * AROUND plugin example: a customer can have at most 5 of any product in the cart.
 *
 * An around method receives:
 *   1. $subject  - the Cart object
 *   2. $proceed  - a callable that runs the original method (and later plugins)
 *   3+. the original arguments
 * It decides whether to call $proceed at all. Here, if the change would go over
 * the limit, $proceed() is never called, so the cart is left unchanged.
 */
class MaxQtyPerProduct
{
    /**
     * @var QtyLimit
     */
    private $qtyLimit;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @param QtyLimit $qtyLimit
     * @param ProductResource $productResource
     */
    public function __construct(QtyLimit $qtyLimit, ProductResource $productResource)
    {
        $this->qtyLimit = $qtyLimit;
        $this->productResource = $productResource;
    }

    /**
     * "Add to Cart" (product page, category listing).
     *
     * @param Cart $subject
     * @param callable $proceed
     * @param int|Product $productInfo
     * @param DataObject|int|float|string|array|null $requestInfo
     * @return Cart
     * @throws LocalizedException
     */
    public function aroundAddProduct(Cart $subject, callable $proceed, $productInfo, $requestInfo = null)
    {
        // Limited releases (Max Pairs Per Customer set) follow Codilar_SneakerDrop's
        // own rule, which also lowers a too-big qty instead of rejecting it.
        if ($this->isLimitedRelease($productInfo)) {
            return $proceed($productInfo, $requestInfo);
        }

        $productId = (int)($productInfo instanceof Product ? $productInfo->getId() : $productInfo);

        $violation = $this->qtyLimit->findViolation(
            $subject->getQuote(),
            [],
            $productId,
            $this->getRequestedQty($requestInfo)
        );

        if ($violation !== null) {
            if ($violation['name'] === '' && $productInfo instanceof Product) {
                $violation['name'] = (string)$productInfo->getName();
            }
            // Original addProduct() is skipped; Magento shows this text to the customer.
            throw new LocalizedException($this->qtyLimit->message($violation));
        }

        // Within the limit: run the original method and return its result unchanged.
        return $proceed($productInfo, $requestInfo);
    }

    /**
     * Changing quantities: cart page "Update Shopping Cart" and the mini-cart qty box.
     *
     * @param Cart $subject
     * @param callable $proceed
     * @param array $data [itemId => ['qty' => .., 'remove' => ..]]
     * @return Cart
     * @throws LocalizedException
     */
    public function aroundUpdateItems(Cart $subject, callable $proceed, $data)
    {
        $violation = $this->qtyLimit->findViolation($subject->getQuote(), $this->qtyLimit->toChanges($data));

        if ($violation !== null) {
            throw new LocalizedException($this->qtyLimit->message($violation));
        }

        return $proceed($data);
    }

    /**
     * Read qty from any of the request shapes addProduct() accepts; missing means 1.
     *
     * @param mixed $requestInfo
     * @return float
     */
    private function getRequestedQty($requestInfo): float
    {
        if ($requestInfo instanceof DataObject) {
            $qty = $requestInfo->getData('qty');
        } elseif (is_array($requestInfo)) {
            $qty = $requestInfo['qty'] ?? null;
        } else {
            $qty = $requestInfo;
        }

        return is_numeric($qty) && (float)$qty > 0 ? (float)$qty : 1.0;
    }

    /**
     * Product has "Max Pairs Per Customer" set (Codilar_SneakerDrop).
     *
     * @param int|Product $productInfo
     * @return bool
     */
    private function isLimitedRelease($productInfo): bool
    {
        if ($productInfo instanceof Product && $productInfo->hasData('max_per_customer')) {
            $value = $productInfo->getData('max_per_customer');
        } else {
            $productId = (int)($productInfo instanceof Product ? $productInfo->getId() : $productInfo);
            $value = $productId ? $this->productResource->getAttributeRawValue($productId, 'max_per_customer', 0) : null;
        }

        return is_numeric($value) && (int)$value > 0;
    }
}
