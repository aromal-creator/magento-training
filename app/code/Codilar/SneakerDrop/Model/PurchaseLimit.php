<?php
declare(strict_types=1);

namespace Codilar\SneakerDrop\Model;

use Codilar\SneakerDrop\Setup\Patch\Data\AddMaxPerCustomerAttribute;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

/**
 * The "max N per customer, ever" rule, shared by the Cart preference and the
 * checkout observer.
 */
class PurchaseLimit
{
    private ResourceConnection $resource;
    private ProductResource $productResource;

    public function __construct(ResourceConnection $resource, ProductResource $productResource)
    {
        $this->resource = $resource;
        $this->productResource = $productResource;
    }

    /**
     * The product's limit, or 0 for "no limit".
     */
    public function getLimit(Product $product): int
    {
        $code = AddMaxPerCustomerAttribute::ATTRIBUTE_CODE;
        $value = $product->hasData($code)
            ? $product->getData($code)
            : $this->productResource->getAttributeRawValue((int)$product->getId(), $code, (int)$product->getStoreId());

        return is_numeric($value) && (int)$value > 0 ? (int)$value : 0;
    }

    /**
     * Units of this product the customer has ordered before (cancelled units excluded).
     */
    public function getOrderedQty(int $customerId, int $productId): float
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['item' => $this->resource->getTableName('sales_order_item')], [])
            ->join(['ord' => $this->resource->getTableName('sales_order')], 'ord.entity_id = item.order_id', [])
            ->columns(['qty' => new \Zend_Db_Expr('SUM(item.qty_ordered - item.qty_canceled)')])
            ->where('ord.customer_id = ?', $customerId)
            ->where('item.product_id = ?', $productId)
            ->where('item.parent_item_id IS NULL')
            ->where('ord.state <> ?', Order::STATE_CANCELED);

        return max(0.0, (float)$connection->fetchOne($select));
    }

    /**
     * Units of this product already in the cart.
     */
    public function getQtyInQuote(Quote $quote, int $productId): float
    {
        $qty = 0.0;
        foreach ($quote->getAllVisibleItems() as $item) {
            if ((int)$item->getProductId() === $productId) {
                $qty += (float)$item->getQty();
            }
        }

        return $qty;
    }

    /**
     * Customer-facing message.
     */
    public function message(int $limit, float $alreadyHave): Phrase
    {
        if ($alreadyHave > 0) {
            return __(
                'This is a limited release. Only %1 per customer. You already have %2 (ordered or in your cart).',
                $limit,
                $alreadyHave
            );
        }

        return __('This is a limited release. Only %1 per customer.', $limit);
    }
}
