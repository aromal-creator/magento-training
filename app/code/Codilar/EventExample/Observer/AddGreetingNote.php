<?php
declare(strict_types=1);

namespace Codilar\EventExample\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * STEP 3 - THE OBSERVER.
 *
 * Runs whenever "codilar_greeting_before_show" is dispatched (see etc/frontend/events.xml).
 * Reads the data the event passed and changes the greeting.
 */
class AddGreetingNote implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        $greeting = $event->getData('greeting');   // the DataObject from the controller
        $name = $event->getData('customer_name');

        $greeting->setData(
            'text',
            $greeting->getData('text') . ' Welcome to the store, ' . $name . '. (added by an observer)'
        );
    }
}
