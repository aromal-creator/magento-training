<?php

namespace Codilar\CronDemo\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * /crondemo: shows the heartbeat cron job's recent runs.
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
        $resultPage->getConfig()->getTitle()->set(__('Cron Demo: Heartbeat Runs'));

        return $resultPage;
    }
}
