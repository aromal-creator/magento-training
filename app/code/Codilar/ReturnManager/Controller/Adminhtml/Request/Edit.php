<?php

namespace Codilar\ReturnManager\Controller\Adminhtml\Request;

use Codilar\ReturnManager\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use Codilar\ReturnManager\Model\ReturnRequestFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Shows the add/edit form for one return request.
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Codilar_ReturnManager::save';

    private PageFactory $resultPageFactory;
    private ReturnRequestFactory $returnRequestFactory;
    private ReturnRequestResource $returnRequestResource;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ReturnRequestFactory $returnRequestFactory,
        ReturnRequestResource $returnRequestResource
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->returnRequestFactory = $returnRequestFactory;
        $this->returnRequestResource = $returnRequestResource;
    }

    public function execute(): ResultInterface
    {
        $id = (int) $this->getRequest()->getParam('id');

        if ($id) {
            $returnRequest = $this->returnRequestFactory->create();
            $this->returnRequestResource->load($returnRequest, $id);
            if (!$returnRequest->getId()) {
                $this->messageManager->addErrorMessage(__('This return request no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu($id ? 'Codilar_ReturnManager::manage' : 'Codilar_ReturnManager::add');
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Edit Return #%1', $id) : __('Add Return')
        );

        return $resultPage;
    }
}
