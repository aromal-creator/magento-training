<?php
declare(strict_types=1);

namespace Codilar\OrderCoupon\Observer;

use Codilar\OrderCoupon\Setup\Patch\Data\CreateThankYouRule;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Api\CouponManagementInterface;
use Magento\SalesRule\Api\Data\CouponGenerationSpecInterfaceFactory;
use Psr\Log\LoggerInterface;

/**
 * After an order is placed, generates a one-time coupon for the customer's next order.
 *
 * The code is:
 *  - added as an order comment visible to the customer (My Account > My Orders > order),
 *  - shown as a message on the next page (the checkout success page),
 *  - written to var/log/system.log.
 *
 * Errors are logged and swallowed: the order is already placed, and a coupon
 * problem must never show the customer a checkout error.
 */
class GiveNextOrderCoupon implements ObserverInterface
{
    private CouponManagementInterface $couponManagement;
    private CouponGenerationSpecInterfaceFactory $specFactory;
    private OrderStatusHistoryRepositoryInterface $historyRepository;
    private ScopeConfigInterface $scopeConfig;
    private ManagerInterface $messageManager;
    private LoggerInterface $logger;

    public function __construct(
        CouponManagementInterface $couponManagement,
        CouponGenerationSpecInterfaceFactory $specFactory,
        OrderStatusHistoryRepositoryInterface $historyRepository,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager,
        LoggerInterface $logger
    ) {
        $this->couponManagement = $couponManagement;
        $this->specFactory = $specFactory;
        $this->historyRepository = $historyRepository;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var Order|null $order */
        $order = $observer->getEvent()->getData('order');
        $ruleId = (int)$this->scopeConfig->getValue(CreateThankYouRule::XML_PATH_RULE_ID);

        if (!$order || !$order->getId() || !$ruleId) {
            return;
        }

        try {
            $spec = $this->specFactory->create()
                ->setRuleId($ruleId)
                ->setQuantity(1)
                ->setLength(8)
                ->setFormat('alphanum')
                ->setPrefix('THANKS-');

            $codes = $this->couponManagement->generate($spec);
            $code = (string)reset($codes);
            if ($code === '') {
                return;
            }

            $text = (string)__('Thank you for your order! Use coupon %1 for 10% off your next order.', $code);

            // Comment on the order, visible to the customer, saved directly
            // because the order itself has already been saved at this point.
            $history = $order->addCommentToStatusHistory($text, false, true);
            $history->setIsCustomerNotified(false)->setParentId($order->getId());
            $this->historyRepository->save($history);

            $this->messageManager->addSuccessMessage($text);

            $this->logger->info(sprintf(
                'Codilar OrderCoupon: coupon %s for order #%s (%s)',
                $code,
                $order->getIncrementId(),
                $order->getCustomerEmail()
            ));
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'Codilar OrderCoupon: could not create coupon for order #%s: %s',
                $order->getIncrementId(),
                $e->getMessage()
            ));
        }
    }
}
