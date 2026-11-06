<?php

namespace Codilar\StoreConfigCache\ViewModel;

use Codilar\StoreConfigCache\Model\StoreConfigCache;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Data for /storeconfig: the current store view's values and whether they came from the cache.
 */
class StoreConfigView implements ArgumentInterface
{
    private StoreConfigCache $storeConfigCache;
    private StoreManagerInterface $storeManager;

    public function __construct(StoreConfigCache $storeConfigCache, StoreManagerInterface $storeManager)
    {
        $this->storeConfigCache = $storeConfigCache;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array{store_name: string, store_id: int, cache_key: string, hit: bool,
     *               cached_at: int, expires_at: int, values: array}
     */
    public function getResult(): array
    {
        $store = $this->storeManager->getStore();
        $storeId = (int)$store->getId();

        // Checked before get(), because get() fills the cache on a miss
        $hit = $this->storeConfigCache->isCached($storeId);
        $data = $this->storeConfigCache->get($storeId);

        return [
            'store_name' => (string)$store->getName(),
            'store_id' => $storeId,
            'cache_key' => $this->storeConfigCache->getCacheId($storeId),
            'hit' => $hit,
            'cached_at' => (int)$data['cached_at'],
            'expires_at' => (int)$data['cached_at'] + StoreConfigCache::CACHE_LIFETIME,
            'values' => $data['values'],
        ];
    }
}
