<?php
declare(strict_types=1);

namespace Codilar\OrderSuccessAlert\ViewModel;

use Codilar\OrderSuccessAlert\Observer\ShowOrderSuccessMessage;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Gives the success page template the alert text the observer saved, once.
 */
class OrderAlert implements ArgumentInterface
{
    private CheckoutSession $checkoutSession;

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns the alert text and removes it from the session, or '' if there is none.
     */
    public function takeAlertText(): string
    {
        $text = (string)$this->checkoutSession->getData(ShowOrderSuccessMessage::ALERT_KEY);
        $this->checkoutSession->unsetData(ShowOrderSuccessMessage::ALERT_KEY);

        return $text;
    }
}
