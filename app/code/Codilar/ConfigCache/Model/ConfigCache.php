<?php

namespace Codilar\ConfigCache\Model;

use Codilar\ConfigCache\Model\Cache\Type;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reads a few store configuration values and caches them per store view for one hour.
 */
class ConfigCache
{
    /** Cache entries live for exactly one hour */
    public const CACHE_LIFETIME = 3600;

    /** Unique to this module; the store ID is appended so each store view gets its own entry */
    private const CACHE_ID_PREFIX = 'CODILAR_CONFIG_CACHE_STORE_';

    /** Label => config path. These are the paths the observer watches too. */
    public const CONFIG_PATHS = [
        'store_name' => 'general/store_information/name',
        'store_email' => 'trans_email/ident_general/email',
        'store_phone' => 'general/store_information/phone',
        'country' => 'general/store_information/country_id',
    ];

    private ScopeConfigInterface $scopeConfig;
    private CacheInterface $cache;
    private StateInterface $cacheState;
    private SerializerInterface $serializer;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CacheInterface $cache,
        StateInterface $cacheState,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cache = $cache;
        $this->cacheState = $cacheState;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns ['values' => [...], 'cached_at' => timestamp] for one store view.
     *
     * Cache hit: the saved copy is returned. Cache miss (never saved, expired or cleared):
     * the values are read from configuration and saved again for CACHE_LIFETIME seconds.
     */
    public function get(int $storeId): array
    {
        $cached = $this->load($storeId);
        if ($cached !== null) {
            return $cached;
        }

        $data = [
            'values' => $this->readFromConfig($storeId),
            'cached_at' => time(),
        ];

        // Respect the on/off switch in System > Cache Management
        if ($this->isEnabled()) {
            $this->cache->save(
                $this->serializer->serialize($data),
                $this->getCacheId($storeId),
                [Type::CACHE_TAG],
                self::CACHE_LIFETIME
            );
        }

        return $data;
    }

    /**
     * True when a valid (not expired) entry exists for the store.
     */
    public function isCached(int $storeId): bool
    {
        return $this->load($storeId) !== null;
    }

    /**
     * Removes the entries of every store view.
     */
    public function clean(): void
    {
        // All entries share Type::CACHE_TAG, so one call normally removes them all
        $this->cache->clean([Type::CACHE_TAG]);

        // The file cache keeps tags in a separate index that another process (e.g. cron) can
        // rewrite at the same moment, so also remove each store view's key directly
        foreach ($this->storeManager->getStores(true) as $store) {
            $this->cache->remove($this->getCacheId((int)$store->getId()));
        }
    }

    public function getCacheId(int $storeId): string
    {
        return self::CACHE_ID_PREFIX . $storeId;
    }

    private function load(int $storeId): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        // load() returns false when the entry is missing or has expired
        $cached = $this->cache->load($this->getCacheId($storeId));
        if (!$cached) {
            return null;
        }

        $data = $this->serializer->unserialize($cached);
        return is_array($data) ? $data : null;
    }

    /**
     * Store scope: Magento returns the store view's value, falling back to the website's, then the default.
     */
    private function readFromConfig(int $storeId): array
    {
        $values = [];
        foreach (self::CONFIG_PATHS as $label => $path) {
            $values[$label] = (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        }

        return $values;
    }

    private function isEnabled(): bool
    {
        return $this->cacheState->isEnabled(Type::TYPE_IDENTIFIER);
    }
}
