<?php
declare(strict_types=1);

namespace Codilar\CartQuantityPlugin\Plugin;

use Magento\Checkout\Model\Cart;
use Magento\Framework\DataObject;

/**
 * Turns "add 1 to cart" into "add 2 to cart".
 *
 * Cart::addProduct($productInfo, $requestInfo) accepts the request as an array
 * (normal Add to Cart form), a number (just the qty) or a DataObject, so all
 * three shapes are handled. Quantities other than 1 are left alone.
 */
class ForceQuantityTwo
{
    private const FROM_QTY = 1.0;
    private const TO_QTY = 2;

    /**
     * @param Cart $subject
     * @param int|\Magento\Catalog\Model\Product $productInfo
     * @param DataObject|int|float|string|array|null $requestInfo
     * @return array New arguments for addProduct()
     */
    public function beforeAddProduct(Cart $subject, $productInfo, $requestInfo = null): array
    {
        if ($requestInfo instanceof DataObject) {
            if ($this->isSingle($requestInfo->getData('qty'))) {
                $requestInfo->setData('qty', self::TO_QTY);
            }
        } elseif (is_array($requestInfo)) {
            if ($this->isSingle($requestInfo['qty'] ?? null)) {
                $requestInfo['qty'] = self::TO_QTY;
            }
        } elseif ($requestInfo === null || is_numeric($requestInfo)) {
            if ($this->isSingle($requestInfo)) {
                $requestInfo = self::TO_QTY;
            }
        }

        return [$productInfo, $requestInfo];
    }

    /**
     * A missing qty means 1 (e.g. "Add to Cart" from a category listing).
     *
     * @param mixed $qty
     * @return bool
     */
    private function isSingle($qty): bool
    {
        if ($qty === null || $qty === '') {
            return true;
        }

        return is_numeric($qty) && (float)$qty === self::FROM_QTY;
    }
}
