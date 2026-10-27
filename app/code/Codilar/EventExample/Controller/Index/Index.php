<?php
declare(strict_types=1);

namespace Codilar\EventExample\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManager;

/**
 * STEP 1 - THE EVENT.
 *
 * Opens at /eventexample?name=Aromal. Builds a greeting, then DISPATCHES the
 * event "codilar_greeting_before_show" so other code can react or change it.
 * This controller does not know which observers exist (if any).
 */
class Index implements HttpGetActionInterface
{
    private EventManager $eventManager;
    private RequestInterface $request;
    private RawFactory $rawFactory;

    public function __construct(EventManager $eventManager, RequestInterface $request, RawFactory $rawFactory)
    {
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->rawFactory = $rawFactory;
    }

    public function execute()
    {
        $name = trim((string)$this->request->getParam('name', 'Guest')) ?: 'Guest';

        // A DataObject is passed so observers can CHANGE the greeting.
        $greeting = new DataObject(['text' => 'Hello, ' . $name . '!']);
        $before = $greeting->getData('text');

        // Fire the event: event name + data for the observers.
        $this->eventManager->dispatch('codilar_greeting_before_show', [
            'greeting' => $greeting,
            'customer_name' => $name, // not 'name': the Event object uses that key for the event's own name
        ]);

        $after = $greeting->getData('text');

        return $this->rawFactory->create()
            ->setHeader('Content-Type', 'text/plain; charset=utf-8')
            ->setContents(
                "Before the event : {$before}\n"
                . "After the event  : {$after}\n\n"
                . "The controller fired 'codilar_greeting_before_show'.\n"
                . "Codilar\\EventExample\\Observer\\AddGreetingNote changed the text.\n"
                . "Try: /eventexample?name=YourName"
            );
    }
}
