<?php

namespace Codilar\ConfigCache\Observer;

use Codilar\ConfigCache\Model\ConfigCache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Clears the config cache when an admin changes one of the cached values.
 *
 * Event data passed by the dispatcher: 'website', 'store' and 'changed_paths' (paths whose value changed).
 */
class ClearConfigCache implements ObserverInterface
{
    private ConfigCache $configCache;

    public function __construct(ConfigCache $configCache)
    {
        $this->configCache = $configCache;
    }

    public function execute(Observer $observer)
    {
        $changedPaths = (array)$observer->getEvent()->getData('changed_paths');

        // Saving the section without changing our values leaves the cache alone
        if (array_intersect($changedPaths, ConfigCache::CONFIG_PATHS)) {
            // A website or default value is inherited by many store views, so clear all of them
            $this->configCache->clean();
        }
    }
}
