<?php
declare(strict_types=1);

namespace Codilar\CartQuantityPlugin\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Message\ManagerInterface;

/**
 * AFTER plugin example.
 *
 * Runs after Cart::addProduct() has finished. An after method receives:
 *   1. $subject  - the Cart object
 *   2. $result   - what addProduct() returned (the Cart itself, for chaining)
 *   3+. the ORIGINAL arguments ($productInfo, $requestInfo), as passed in
 * Whatever this method returns becomes the new return value, so it returns
 * $result unchanged to keep the cart behaving normally.
 */
class CartQtyNotice
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param ManagerInterface $messageManager
     */
    public function __construct(ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * @param Cart $subject
     * @param Cart $result
     * @param int|Product $productInfo
     * @param mixed $requestInfo
     * @return Cart
     */
    public function afterAddProduct(Cart $subject, $result, $productInfo, $requestInfo = null)
    {
        $productId = (int)($productInfo instanceof Product ? $productInfo->getId() : $productInfo);

        $qtyInCart = 0.0;
        $name = '';
        foreach ($subject->getQuote()->getAllVisibleItems() as $item) {
            if ((int)$item->getProductId() === $productId) {
                $qtyInCart += (float)$item->getQty();
                $name = (string)$item->getName();
            }
        }

        if ($qtyInCart > 0) {
            $this->messageManager->addNoticeMessage(
                __('Codilar: you now have %1 x "%2" in your cart.', $qtyInCart, $name)
            );
        }

        return $result;
    }
}
