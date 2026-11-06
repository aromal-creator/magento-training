<?php

namespace Codilar\StoreConfigCache\Observer;

use Codilar\StoreConfigCache\Model\StoreConfigCache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Clears the cache when an admin saves a configuration section and one of our paths really changed.
 *
 * Event data: 'website', 'store' and 'changed_paths' (only the paths whose value changed).
 */
class ClearOnConfigSave implements ObserverInterface
{
    private StoreConfigCache $storeConfigCache;

    public function __construct(StoreConfigCache $storeConfigCache)
    {
        $this->storeConfigCache = $storeConfigCache;
    }

    public function execute(Observer $observer)
    {
        $changedPaths = (array)$observer->getEvent()->getData('changed_paths');

        if (array_intersect($changedPaths, $this->storeConfigCache->getPaths())) {
            // A default or website value is inherited by many store views, so clear them all
            $this->storeConfigCache->clean();
        }
    }
}
