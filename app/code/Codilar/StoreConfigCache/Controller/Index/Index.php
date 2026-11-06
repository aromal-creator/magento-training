<?php

namespace Codilar\StoreConfigCache\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * /storeconfig: shows the current store view's cached configuration values.
 */
class Index implements HttpGetActionInterface
{
    private PageFactory $resultPageFactory;

    public function __construct(PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Store Config Cache'));

        return $resultPage;
    }
}
