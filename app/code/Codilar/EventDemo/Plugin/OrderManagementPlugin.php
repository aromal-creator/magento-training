<?php

namespace Codilar\EventDemo\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Psr\Log\LoggerInterface;

class OrderManagementPlugin
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Runs before place(); may return modified arguments (or null to keep them).
     */
    public function beforePlace(OrderManagementInterface $subject, OrderInterface $order)
    {
        $this->logger->info(
            'Codilar EventDemo Plugin: before place. Customer email: '
            . $order->getCustomerEmail()
        );

        return null;
    }

    /**
     * Wraps place(); $proceed calls the next plugin or the original method.
     */
    public function aroundPlace(
        OrderManagementInterface $subject,
        callable $proceed,
        OrderInterface $order
    ) {
        $start = microtime(true);
        $result = $proceed($order);

        $this->logger->info(sprintf(
            'Codilar EventDemo Plugin: around place. Took %.2f ms',
            (microtime(true) - $start) * 1000
        ));

        return $result;
    }

    /**
     * Runs after place(); must return the (possibly modified) result.
     */
    public function afterPlace(OrderManagementInterface $subject, OrderInterface $result)
    {
        $this->logger->info(
            'Codilar EventDemo Plugin: after place. Order ID: '
            . $result->getIncrementId()
        );

        return $result;
    }
}
