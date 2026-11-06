<?php

namespace Codilar\WelcomeEmailQueue\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Queue handler (etc/queue_consumer.xml): receives one customer ID per message and sends the welcome email.
 */
class WelcomeEmailHandler
{
    private const TEMPLATE_ID = 'codilar_welcome_email_template';

    /** Sender identity from Stores > Configuration > General > Store Email Addresses */
    private const SENDER = 'general';

    private CustomerRepositoryInterface $customerRepository;
    private TransportBuilder $transportBuilder;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * The parameter type matches request="int" in etc/communication.xml.
     */
    public function process(int $customerId): void
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $storeId = (int)$customer->getStoreId();
            $name = trim($customer->getFirstname() . ' ' . $customer->getLastname());

            $transport = $this->transportBuilder
                ->setTemplateIdentifier(self::TEMPLATE_ID)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars([
                    'customer_name' => $name,
                    'store_name' => $this->storeManager->getStore($storeId)->getFrontendName(),
                ])
                ->setFromByScope(self::SENDER, $storeId)
                ->addTo($customer->getEmail(), $name)
                ->getTransport();
            $transport->sendMessage();

            $this->logger->info(sprintf('SENT customer %d to %s', $customerId, $customer->getEmail()));
        } catch (\Throwable $e) {
            // Logged and not re-thrown, so one bad message (e.g. a deleted customer) does not block the queue
            $this->logger->error(sprintf('FAILED customer %d: %s', $customerId, $e->getMessage()));
        }
    }
}
