<?php
declare(strict_types=1);

namespace Codilar\EventDemo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Observes the core "customer_login" event and greets the customer by name.
 *
 * Event data passed by Magento: 'customer' (the logged-in customer model).
 */
class WelcomeBackObserver implements ObserverInterface
{
    private ManagerInterface $messageManager;

    public function __construct(ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getData('customer');

        if ($customer) {
            $this->messageManager->addSuccessMessage(
                __('Welcome back, %1! (message from an event observer)', $customer->getFirstname())
            );
        }
    }
}
