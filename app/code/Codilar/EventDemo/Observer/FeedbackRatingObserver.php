<?php
declare(strict_types=1);

namespace Codilar\EventDemo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Observes the custom "codilar_customer_feedback_save_after" event.
 *
 * Event data passed by the dispatcher: 'feedback' (the saved feedback model).
 * Low ratings (1-2) get an apology on screen and a warning in var/log/system.log.
 */
class FeedbackRatingObserver implements ObserverInterface
{
    private const LOW_RATING = 2;

    private ManagerInterface $messageManager;
    private LoggerInterface $logger;

    public function __construct(ManagerInterface $messageManager, LoggerInterface $logger)
    {
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $feedback = $observer->getEvent()->getData('feedback');
        if (!$feedback) {
            return;
        }

        $rating = (int)$feedback->getData('rating');

        if ($rating <= self::LOW_RATING) {
            $this->messageManager->addNoticeMessage(__(
                'Sorry you were not happy, %1. Our team will contact you at %2. (message from an event observer)',
                $feedback->getData('customer_name'),
                $feedback->getData('email')
            ));
            $this->logger->warning(sprintf(
                'Codilar EventDemo: low feedback rating %d from %s (feedback id %s)',
                $rating,
                $feedback->getData('email'),
                $feedback->getId()
            ));
        } else {
            $this->logger->info(sprintf(
                'Codilar EventDemo: feedback rating %d from %s',
                $rating,
                $feedback->getData('email')
            ));
        }
    }
}
