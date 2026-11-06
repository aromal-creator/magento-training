<?php

namespace Codilar\StoreConfigCache\Controller\Adminhtml\Cache;

use Codilar\StoreConfigCache\Model\StoreConfigCache;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Admin URL codilarstoreconfig/cache/clear: clears the cache, then returns to the configuration page.
 */
class Clear extends Action implements HttpGetActionInterface
{
    /** Only admins allowed to see the Store Config Cache settings may clear it */
    public const ADMIN_RESOURCE = 'Codilar_StoreConfigCache::config';

    private StoreConfigCache $storeConfigCache;

    public function __construct(Context $context, StoreConfigCache $storeConfigCache)
    {
        $this->storeConfigCache = $storeConfigCache;
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $this->storeConfigCache->clean();
        $this->messageManager->addSuccessMessage(__('The store config cache was cleared for all store views.'));

        return $this->resultRedirectFactory->create()
            ->setPath('adminhtml/system_config/edit', ['section' => 'codilar_store_config_cache']);
    }
}
