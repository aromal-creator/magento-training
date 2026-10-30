<?php

namespace Codilar\ReturnManager\Controller\Adminhtml\Request;

use Codilar\ReturnManager\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use Codilar\ReturnManager\Model\ResourceModel\ReturnRequest\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Deletes the return requests selected in the grid.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Codilar_ReturnManager::delete';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private ReturnRequestResource $returnRequestResource;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ReturnRequestResource $returnRequestResource
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->returnRequestResource = $returnRequestResource;
    }

    public function execute(): Redirect
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $deleted = 0;
            foreach ($collection as $returnRequest) {
                $this->returnRequestResource->delete($returnRequest);
                $deleted++;
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $deleted));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while deleting the return requests.')
            );
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
