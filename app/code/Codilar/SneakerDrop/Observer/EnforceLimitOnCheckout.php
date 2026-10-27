<?php
declare(strict_types=1);

namespace Codilar\SneakerDrop\Observer;

use Codilar\SneakerDrop\Model\PurchaseLimit;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

/**
 * Last check before the order is created (sales_model_service_quote_submit_before).
 * Throwing here stops the order and shows the message at checkout.
 */
class EnforceLimitOnCheckout implements ObserverInterface
{
    private PurchaseLimit $purchaseLimit;

    public function __construct(PurchaseLimit $purchaseLimit)
    {
        $this->purchaseLimit = $purchaseLimit;
    }

    public function execute(Observer $observer)
    {
        /** @var Quote|null $quote */
        $quote = $observer->getEvent()->getData('quote');
        if (!$quote) {
            return;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $limit = $product ? $this->purchaseLimit->getLimit($product) : 0;
            if ($limit === 0) {
                continue;
            }

            $customerId = (int)$quote->getCustomerId();
            if (!$customerId || $quote->getCustomerIsGuest()) {
                throw new LocalizedException(__('Please sign in to buy this limited release.'));
            }

            $ordered = $this->purchaseLimit->getOrderedQty($customerId, (int)$item->getProductId());
            $inCart = $this->purchaseLimit->getQtyInQuote($quote, (int)$item->getProductId());
            if ($ordered + $inCart > $limit) {
                throw new LocalizedException(__(
                    '%1: %2',
                    $item->getName(),
                    $this->purchaseLimit->message($limit, $ordered)
                ));
            }
        }
    }
}
