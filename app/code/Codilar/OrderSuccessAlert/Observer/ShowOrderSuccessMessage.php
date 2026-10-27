<?php
declare(strict_types=1);

namespace Codilar\OrderSuccessAlert\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Runs on "checkout_onepage_controller_success_action" (see etc/frontend/events.xml),
 * which Magento dispatches on the checkout success page only after a real order.
 *
 * Event data: 'order' (the order just placed), 'order_ids'.
 *
 * Saves the alert text in the checkout session; ViewModel\OrderAlert reads it
 * once on the same page and the template shows it as a popup alert.
 * The order id is remembered so a page reload does not show the alert again.
 */
class ShowOrderSuccessMessage implements ObserverInterface
{
    public const ALERT_KEY = 'codilar_order_success_alert_text';
    private const SHOWN_ORDER_KEY = 'codilar_order_success_alert_order_id';

    private CheckoutSession $checkoutSession;

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(Observer $observer)
    {
        /** @var Order|null $order */
        $order = $observer->getEvent()->getData('order');
        if (!$order || !$order->getId()) {
            return;
        }

        $orderId = (int)$order->getId();
        if ((int)$this->checkoutSession->getData(self::SHOWN_ORDER_KEY) === $orderId) {
            return; // already shown for this order
        }
        $this->checkoutSession->setData(self::SHOWN_ORDER_KEY, $orderId);

        $this->checkoutSession->setData(
            self::ALERT_KEY,
            (string)__('Thank you! Your order #%1 has been placed successfully.', $order->getIncrementId())
        );
    }
}
