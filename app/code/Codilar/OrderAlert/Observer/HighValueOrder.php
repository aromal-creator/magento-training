<?php
declare(strict_types=1);

namespace Codilar\OrderAlert\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Flags high-value orders so the team checks them before shipping.
 *
 * Adds an internal comment to the order's history (Sales > Orders > order >
 * Comments History) and writes a warning to var/log/system.log.
 *
 * No $order->save() here: the event fires inside Order::place() and Magento
 * saves the order (with this comment) right after all observers have run.
 */
class HighValueOrder implements ObserverInterface
{
    /**
     * Compared with the order's grand total in the base currency (INR for this store).
     */
    private const THRESHOLD = 10000;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var Order|null $order */
        $order = $observer->getEvent()->getData('order');
        if (!$order) {
            return;
        }

        $total = (float)$order->getBaseGrandTotal();
        if ($total <= self::THRESHOLD) {
            return;
        }

        $order->addCommentToStatusHistory(
            (string)__('High-value order (%1), verify before shipping.', $order->getBaseCurrency()->formatTxt($total)),
            false,
            false
        )->setIsCustomerNotified(false);

        $this->logger->warning(sprintf(
            'Codilar OrderAlert: high-value order #%s, base grand total %.2f %s, customer %s',
            $order->getIncrementId(),
            $total,
            $order->getBaseCurrencyCode(),
            $order->getCustomerEmail()
        ));
    }
}
