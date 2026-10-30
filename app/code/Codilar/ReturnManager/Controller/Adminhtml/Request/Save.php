<?php

namespace Codilar\ReturnManager\Controller\Adminhtml\Request;

use Codilar\ReturnManager\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use Codilar\ReturnManager\Model\ReturnRequestFactory;
use Codilar\ReturnManager\Model\Source\Reason;
use Codilar\ReturnManager\Model\Source\Status;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

/**
 * Saves the add/edit form (create or update).
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Codilar_ReturnManager::save';
    public const DATA_PERSISTOR_KEY = 'codilar_return_request';

    private ReturnRequestFactory $returnRequestFactory;
    private ReturnRequestResource $returnRequestResource;
    private DataPersistorInterface $dataPersistor;
    private Reason $reasonSource;
    private Status $statusSource;

    public function __construct(
        Context $context,
        ReturnRequestFactory $returnRequestFactory,
        ReturnRequestResource $returnRequestResource,
        DataPersistorInterface $dataPersistor,
        Reason $reasonSource,
        Status $statusSource
    ) {
        parent::__construct($context);
        $this->returnRequestFactory = $returnRequestFactory;
        $this->returnRequestResource = $returnRequestResource;
        $this->dataPersistor = $dataPersistor;
        $this->reasonSource = $reasonSource;
        $this->statusSource = $statusSource;
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = (int) ($data['entity_id'] ?? 0);
        $returnRequest = $this->returnRequestFactory->create();

        try {
            if ($id) {
                $this->returnRequestResource->load($returnRequest, $id);
                if (!$returnRequest->getId()) {
                    throw new LocalizedException(__('This return request no longer exists.'));
                }
            }

            $returnRequest->setOrderId($this->requireText($data, 'order_id', (string) __('Order ID')));
            $returnRequest->setProductSku($this->requireText($data, 'product_sku', (string) __('Product SKU')));
            $returnRequest->setReason(
                $this->requireOption($data, 'reason', $this->reasonSource->toOptionArray(), (string) __('Return Reason'))
            );
            $returnRequest->setStatus(
                $this->requireOption($data, 'status', $this->statusSource->toOptionArray(), (string) __('Return Status'))
            );

            $this->returnRequestResource->save($returnRequest);
            $this->messageManager->addSuccessMessage(__('The return request has been saved.'));
            $this->dataPersistor->clear(self::DATA_PERSISTOR_KEY);

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $returnRequest->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while saving the return request.')
            );
        }

        // Keep what the admin typed so the form is refilled after the redirect
        $this->dataPersistor->set(self::DATA_PERSISTOR_KEY, $data);

        return $id
            ? $resultRedirect->setPath('*/*/edit', ['id' => $id])
            : $resultRedirect->setPath('*/*/new');
    }

    /**
     * @throws LocalizedException
     */
    private function requireText(array $data, string $field, string $label): string
    {
        $value = trim((string) ($data[$field] ?? ''));
        if ($value === '') {
            throw new LocalizedException(__('"%1" is required.', $label));
        }

        return $value;
    }

    /**
     * @throws LocalizedException
     */
    private function requireOption(array $data, string $field, array $options, string $label): string
    {
        $value = (string) ($data[$field] ?? '');
        if (!in_array($value, array_column($options, 'value'), true)) {
            throw new LocalizedException(__('Please select a valid "%1".', $label));
        }

        return $value;
    }
}
