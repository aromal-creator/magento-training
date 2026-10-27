<?php
declare(strict_types=1);

namespace Codilar\NewsletterMessage\Controller\Subscriber;

use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Newsletter\Model\Config as NewsletterConfig;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Replaces Magento's newsletter Subscribe controller (see etc/frontend/di.xml)
 * to replace its PRIVATE method getSuccessMessage() from
 * vendor/magento/module-newsletter/Controller/Subscriber/NewAction.php.
 *
 * How (preference only):
 *  1. getSuccessMessage() is declared again here as PRIVATE, with our message.
 *  2. execute() (PUBLIC) is overridden with the core code, so that inside THIS
 *     class $this->getSuccessMessage() resolves to OUR private method.
 *     parent::execute() is not called: it would use the core private method.
 *  3. Core private members execute() needs are re-declared here:
 *     getCustomerId() (private method), $subscriptionManager, $customerRepository,
 *     $newsletterConfig (private properties).
 */
class NewAction extends \Magento\Newsletter\Controller\Subscriber\NewAction
{
    private SubscriptionManagerInterface $subscriptionManager;
    private CustomerRepositoryInterface $customerRepository;
    private NewsletterConfig $newsletterConfig;

    public function __construct(
        Context $context,
        SubscriberFactory $subscriberFactory,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerUrl $customerUrl,
        CustomerAccountManagement $customerAccountManagement,
        SubscriptionManagerInterface $subscriptionManager,
        EmailValidator $emailValidator,
        CustomerRepositoryInterface $customerRepository,
        NewsletterConfig $newsletterConfig
    ) {
        parent::__construct(
            $context,
            $subscriberFactory,
            $customerSession,
            $storeManager,
            $customerUrl,
            $customerAccountManagement,
            $subscriptionManager,
            $emailValidator,
            $customerRepository,
            $newsletterConfig
        );
        $this->subscriptionManager = $subscriptionManager;
        $this->customerRepository = $customerRepository;
        $this->newsletterConfig = $newsletterConfig;
    }

    /**
     * Core execute(); the only change is the getSuccessMessage() call, which
     * now resolves to OUR private method (and also receives the email).
     *
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->getRequest()->isPost()
            && $this->getRequest()->getPost('email')
            && $this->newsletterConfig->isActive(ScopeInterface::SCOPE_STORE)
        ) {
            $email = (string)$this->getRequest()->getPost('email');

            try {
                $this->validateEmailFormat($email);
                $this->validateGuestSubscription();
                $this->validateEmailAvailable($email);

                $websiteId = (int)$this->_storeManager->getStore()->getWebsiteId();
                /** @var Subscriber $subscriber */
                $subscriber = $this->_subscriberFactory->create()->loadBySubscriberEmail($email, $websiteId);
                if ($subscriber->getId()
                    && (int)$subscriber->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED) {
                    throw new LocalizedException(
                        __('This email address is already subscribed.')
                    );
                }

                $storeId = (int)$this->_storeManager->getStore()->getId();
                $currentCustomerId = $this->getCustomerId($email, $websiteId);

                $subscriber = $currentCustomerId
                    ? $this->subscriptionManager->subscribeCustomer($currentCustomerId, $storeId)
                    : $this->subscriptionManager->subscribe($email, $storeId);
                $message = $this->getSuccessMessage((int)$subscriber->getSubscriberStatus(), $email);
                $this->messageManager->addSuccessMessage($message);
            } catch (LocalizedException $e) {
                $this->messageManager->addComplexErrorMessage(
                    'localizedSubscriptionErrorMessage',
                    ['message' => $e->getMessage()]
                );
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong with the subscription.'));
            }
        }
        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        // phpcs:ignore Magento2.Legacy.ObsoleteResponse
        $redirectUrl = $this->_redirect->getRedirectUrl();

        return $redirect->setUrl($redirectUrl);
    }

    /**
     * OUR getSuccessMessage(), replacing the core PRIVATE method of the same name.
     *
     * Core: "The confirmation request has been sent." / "Thank you for your subscription."
     *
     * @param int $status
     * @param string $email
     * @return Phrase
     */
    private function getSuccessMessage(int $status, string $email = ''): Phrase
    {
        if ($status === Subscriber::STATUS_NOT_ACTIVE) {
            return __('Almost done! Please check %1 and confirm your email to join the Storefront newsletter.', $email);
        }

        return __('You\'re in! %1 will now get our new arrivals and offers first. Thank you for subscribing.', $email);
    }

    /**
     * Copy of the core private getCustomerId() (execute() needs it).
     *
     * @param string $email
     * @param int $websiteId
     * @return int|null
     */
    private function getCustomerId(string $email, int $websiteId): ?int
    {
        try {
            $customer = $this->customerRepository->get($email, $websiteId);

            return (int)$customer->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
