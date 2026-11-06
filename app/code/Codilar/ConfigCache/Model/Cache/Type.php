<?php

namespace Codilar\ConfigCache\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * The "Codilar Config Cache" cache type.
 *
 * TagScope limits cleaning to entries carrying CACHE_TAG, so flushing this type
 * in Cache Management removes only our entries, not the whole Magento cache.
 */
class Type extends TagScope
{
    public const TYPE_IDENTIFIER = 'codilar_config_cache';
    public const CACHE_TAG = 'CODILAR_CONFIG_CACHE';

    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}
