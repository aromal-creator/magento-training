<?php
declare(strict_types=1);

namespace Codilar\ContactValidation\Controller\Index;

use Magento\Contact\Model\ConfigInterface;
use Magento\Contact\Model\MailInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Replaces Magento's Contact Us submit controller (see etc/frontend/di.xml).
 *
 * The core class validates the form in a PRIVATE method, validatedParams(),
 * which cannot be overridden. Instead of copying core code, this class
 * overrides the PUBLIC execute(): it runs our extra rules first and, if they
 * pass, calls parent::execute(), where the core private validatedParams() and
 * sendEmail() run unchanged.
 *
 * Extra rules (added on top of the core ones):
 *  - email must be a real address (core only checks for "@")
 *  - telephone required, 10-13 digits (core: optional)
 *  - comment at least 10 characters (core: just not empty)
 */
class Post extends \Magento\Contact\Controller\Index\Post
{
    private const MIN_COMMENT_LENGTH = 10;

    private DataPersistorInterface $dataPersistor;

    public function __construct(
        Context $context,
        ConfigInterface $contactsConfig,
        MailInterface $mail,
        DataPersistorInterface $dataPersistor,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($context, $contactsConfig, $mail, $dataPersistor, $logger);
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $this->validateExtraRules();
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                // Keep what the customer typed so the form is refilled.
                $this->dataPersistor->set('contact_us', $this->getRequest()->getParams());

                return $this->resultRedirectFactory->create()->setPath('contact/index');
            }
        }

        // Core execute(): its private validatedParams() and sendEmail() run as usual.
        return parent::execute();
    }

    /**
     * Our rules, checked before the core private validatedParams().
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateExtraRules(): void
    {
        $request = $this->getRequest();

        $email = trim((string)$request->getParam('email', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new LocalizedException(__('The email address is invalid. Verify the email address and try again.'));
        }

        $digits = preg_replace('/\D/', '', (string)$request->getParam('telephone', ''));
        if (strlen($digits) < 10 || strlen($digits) > 13) {
            throw new LocalizedException(__('Enter a valid phone number (10 to 13 digits) and try again.'));
        }

        $comment = trim((string)$request->getParam('comment', ''));
        if (mb_strlen($comment) < self::MIN_COMMENT_LENGTH) {
            throw new LocalizedException(__(
                'Please tell us a bit more: the comment must be at least %1 characters.',
                self::MIN_COMMENT_LENGTH
            ));
        }
    }
}
