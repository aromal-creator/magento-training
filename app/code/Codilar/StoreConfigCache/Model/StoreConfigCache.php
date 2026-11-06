<?php

namespace Codilar\StoreConfigCache\Model;

use Codilar\StoreConfigCache\Model\Cache\Type;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reads the configuration paths listed in etc/di.xml and caches them per store view for one hour.
 */
class StoreConfigCache
{
    /** One hour, in seconds */
    public const CACHE_LIFETIME = 3600;

    /** The store ID is appended, so each store view has its own cache entry */
    private const CACHE_ID_PREFIX = 'CODILAR_STORE_CONFIG_';

    private ScopeConfigInterface $scopeConfig;
    private CacheInterface $cache;
    private StateInterface $cacheState;
    private SerializerInterface $serializer;
    private StoreManagerInterface $storeManager;

    /** @var string[] label => config path, injected from etc/di.xml */
    private array $paths;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CacheInterface $cache,
        StateInterface $cacheState,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager,
        array $paths = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cache = $cache;
        $this->cacheState = $cacheState;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
        $this->paths = $paths;
    }

    /**
     * Returns ['values' => [label => value], 'cached_at' => timestamp] for one store view.
     *
     * Hit: the saved copy. Miss (never saved, expired or cleared): read from config, then saved for one hour.
     */
    public function get(int $storeId): array
    {
        $cached = $this->load($storeId);
        if ($cached !== null) {
            return $cached;
        }

        $data = ['values' => $this->readFromConfig($storeId), 'cached_at' => time()];

        // Respect the Enabled/Disabled switch in System > Cache Management
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

    public function isCached(int $storeId): bool
    {
        return $this->load($storeId) !== null;
    }

    /**
     * Removes the cached values of every store view.
     */
    public function clean(): void
    {
        $this->cache->clean([Type::CACHE_TAG]);

        // The file cache keeps tags in a separate index that another process (e.g. cron) may rewrite
        // at the same moment, so each store view's key is also removed directly
        foreach ($this->storeManager->getStores(true) as $store) {
            $this->cache->remove($this->getCacheId((int)$store->getId()));
        }
    }

    /**
     * @return string[] label => config path
     */
    public function getPaths(): array
    {
        return $this->paths;
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

        // false when the entry is missing or older than one hour
        $cached = $this->cache->load($this->getCacheId($storeId));
        if (!$cached) {
            return null;
        }

        $data = $this->serializer->unserialize($cached);
        return is_array($data) ? $data : null;
    }

    /**
     * Store scope: the store view's own value, else the website's, else the default.
     */
    private function readFromConfig(int $storeId): array
    {
        $values = [];
        foreach ($this->paths as $label => $path) {
            $values[$label] = (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        }

        return $values;
    }

    private function isEnabled(): bool
    {
        return $this->cacheState->isEnabled(Type::TYPE_IDENTIFIER);
    }
}
