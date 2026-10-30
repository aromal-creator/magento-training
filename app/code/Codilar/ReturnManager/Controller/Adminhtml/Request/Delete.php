<?php

namespace Codilar\ReturnManager\Controller\Adminhtml\Request;

use Codilar\ReturnManager\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use Codilar\ReturnManager\Model\ReturnRequestFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Deletes one return request.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Codilar_ReturnManager::delete';

    private ReturnRequestFactory $returnRequestFactory;
    private ReturnRequestResource $returnRequestResource;

    public function __construct(
        Context $context,
        ReturnRequestFactory $returnRequestFactory,
        ReturnRequestResource $returnRequestResource
    ) {
        parent::__construct($context);
        $this->returnRequestFactory = $returnRequestFactory;
        $this->returnRequestResource = $returnRequestResource;
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $id = (int) $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a return request to delete.'));
            return $resultRedirect;
        }

        try {
            $returnRequest = $this->returnRequestFactory->create();
            $this->returnRequestResource->load($returnRequest, $id);
            if (!$returnRequest->getId()) {
                $this->messageManager->addErrorMessage(__('This return request no longer exists.'));
                return $resultRedirect;
            }

            $this->returnRequestResource->delete($returnRequest);
            $this->messageManager->addSuccessMessage(__('The return request has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while deleting the return request.')
            );
            return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
        }

        return $resultRedirect;
    }
}
