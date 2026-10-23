<?php
namespace Codilar\CustomerFeedback\Controller\Index;

use Codilar\CustomerFeedback\Model\FeedbackFactory;
use Codilar\CustomerFeedback\Model\ResourceModel\Feedback as FeedbackResource;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Psr\Log\LoggerInterface;

class Save extends Action implements HttpPostActionInterface
{
    private const MAX_MESSAGE_LENGTH = 2000;

    private FeedbackFactory $feedbackFactory;
    private FeedbackResource $feedbackResource;
    private FormKeyValidator $formKeyValidator;
    private DataPersistorInterface $dataPersistor;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        FeedbackFactory $feedbackFactory,
        FeedbackResource $feedbackResource,
        FormKeyValidator $formKeyValidator,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->feedbackFactory = $feedbackFactory;
        $this->feedbackResource = $feedbackResource;
        $this->formKeyValidator = $formKeyValidator;
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('feedback');

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired. Please submit the form again.')
            );

            return $redirect;
        }

        $data = $this->getRequest()->getPostValue();

        $name = trim((string)($data['customer_name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $rating = (int)($data['rating'] ?? 0);
        $message = trim((string)($data['message'] ?? ''));

        if (
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $rating < 1 || $rating > 5 ||
            $message === '' ||
            mb_strlen($message) > self::MAX_MESSAGE_LENGTH
        ) {
            $this->messageManager->addErrorMessage(
                __('Please fill in all fields with valid values.')
            );
            // Keep what the customer typed so the form is refilled after the redirect.
            $this->dataPersistor->set('customer_feedback', $data);

            return $redirect;
        }

        try {
            $feedback = $this->feedbackFactory->create();
            $feedback->setData([
                'customer_name' => $name,
                'email' => $email,
                'rating' => $rating,
                'message' => $message,
            ]);
            $this->feedbackResource->save($feedback);

            // Custom event: any module can observe this in its events.xml.
            $this->_eventManager->dispatch(
                'codilar_customer_feedback_save_after',
                ['feedback' => $feedback]
            );

            $this->dataPersistor->clear('customer_feedback');
            $this->messageManager->addSuccessMessage(
                __('Thank you for your feedback!')
            );
        } catch (\Exception $e) {
            $this->logger->error('Customer feedback save failed: ' . $e->getMessage());
            $this->dataPersistor->set('customer_feedback', $data);
            $this->messageManager->addErrorMessage(
                __('We could not save your feedback. Please try again later.')
            );
        }

        return $redirect;
    }
}
