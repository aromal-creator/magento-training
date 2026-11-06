<?php

namespace Codilar\WelcomeEmailRabbitMq\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Runs when a customer registers. It only sends the customer ID to RabbitMQ, so the
 * registration page does not wait for the email to be sent.
 */
class PublishWelcomeEmail implements ObserverInterface
{
    public const TOPIC = 'codilar.customer.welcome_email.amqp';

    private PublisherInterface $publisher;
    private LoggerInterface $logger;

    public function __construct(PublisherInterface $publisher, LoggerInterface $logger)
    {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $customerId = (int)$observer->getEvent()->getCustomer()->getId();

        try {
            $this->publisher->publish(self::TOPIC, $customerId);
            $this->logger->info(sprintf('QUEUED customer %d', $customerId));
        } catch (\Throwable $e) {
            // Never break the registration itself because of the welcome email
            $this->logger->error(sprintf('NOT QUEUED customer %d: %s', $customerId, $e->getMessage()));
        }
    }
}
