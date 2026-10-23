<?php
declare(strict_types=1);

namespace Codilar\CartQuantityPlugin\Model;

use Magento\Quote\Model\Quote;

/**
 * The "at most 5 of a product per cart" rule, shared by the around plugins.
 */
class QtyLimit
{
    public const MAX_QTY = 5;

    /**
     * Total qty per product the cart would have after applying $changes.
     *
     * @param Quote $quote
     * @param float[] $changes New qty per quote item id (0 = removed); other items keep their qty
     * @param int|null $addProductId Product being added, if any
     * @param float $addQty Qty being added for $addProductId
     * @return array|null First product over the limit: ['item_id', 'name', 'qty'], or null if all fine
     */
    public function findViolation(Quote $quote, array $changes, ?int $addProductId = null, float $addQty = 0): ?array
    {
        $totals = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productId = (int)$item->getProductId();
            $qty = array_key_exists((int)$item->getId(), $changes)
                ? $changes[(int)$item->getId()]
                : (float)$item->getQty();

            if (!isset($totals[$productId])) {
                $totals[$productId] = ['item_id' => (int)$item->getId(), 'name' => (string)$item->getName(), 'qty' => 0.0];
            }
            $totals[$productId]['qty'] += $qty;
        }

        if ($addProductId !== null) {
            if (!isset($totals[$addProductId])) {
                $totals[$addProductId] = ['item_id' => null, 'name' => '', 'qty' => 0.0];
            }
            $totals[$addProductId]['qty'] += $addQty;
        }

        foreach ($totals as $total) {
            if ($total['qty'] > self::MAX_QTY) {
                return $total;
            }
        }

        return null;
    }

    /**
     * Turn cart form data ([itemId => ['qty' => .., 'remove' => ..]]) into [itemId => newQty].
     *
     * @param mixed $cartData
     * @return float[]
     */
    public function toChanges($cartData): array
    {
        $changes = [];
        foreach (is_array($cartData) ? $cartData : [] as $itemId => $info) {
            if (!is_array($info)) {
                continue;
            }
            if (!empty($info['remove'])) {
                $changes[(int)$itemId] = 0.0;
            } elseif (isset($info['qty']) && is_numeric($info['qty'])) {
                $changes[(int)$itemId] = max(0.0, (float)$info['qty']);
            }
        }

        return $changes;
    }

    /**
     * Customer-facing error text.
     *
     * @param array $violation As returned by findViolation()
     * @return \Magento\Framework\Phrase
     */
    public function message(array $violation)
    {
        return __('You can have at most %1 of "%2" in your cart.', self::MAX_QTY, $violation['name']);
    }
}
