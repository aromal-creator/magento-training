<?php

namespace Codilar\StoreConfigCache\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * The "Codilar Store Config Cache" cache type.
 *
 * Every entry is saved with CACHE_TAG, so refreshing this type in Cache Management
 * removes only this module's entries, not the whole Magento cache.
 */
class Type extends TagScope
{
    public const TYPE_IDENTIFIER = 'codilar_store_config_cache';
    public const CACHE_TAG = 'CODILAR_STORE_CONFIG_CACHE';

    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}
