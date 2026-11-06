<?php

namespace Codilar\SpecialPriceCleanup\Model;

use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexer;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;

/**
 * Finds products whose special price has expired and removes that special price.
 */
class ExpiredSpecialPriceRemover
{
    /** Attributes cleared on an expired product */
    private const ATTRIBUTES_TO_CLEAR = ['special_price', 'special_from_date', 'special_to_date'];

    private CollectionFactory $collectionFactory;
    private ProductAction $productAction;
    private TimezoneInterface $timezone;
    private PriceIndexer $priceIndexer;
    private CacheContext $cacheContext;
    private EventManager $eventManager;

    public function __construct(
        CollectionFactory $collectionFactory,
        ProductAction $productAction,
        TimezoneInterface $timezone,
        PriceIndexer $priceIndexer,
        CacheContext $cacheContext,
        EventManager $eventManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->productAction = $productAction;
        $this->timezone = $timezone;
        $this->priceIndexer = $priceIndexer;
        $this->cacheContext = $cacheContext;
        $this->eventManager = $eventManager;
    }

    /**
     * Returns the IDs of products that have a special price with an end date before today.
     *
     * The end date is the last valid day, so a price ending today is still active today.
     *
     * @return int[]
     */
    public function findExpiredProductIds(): array
    {
        // "Today" in the store's timezone (Stores > Configuration > General > Locale Options)
        $today = $this->timezone->date()->format('Y-m-d 00:00:00');

        $collection = $this->collectionFactory->create();
        $collection->setStoreId(Store::DEFAULT_STORE_ID)
            ->addAttributeToFilter('special_price', ['notnull' => true])
            ->addAttributeToFilter('special_to_date', ['lt' => $today]);

        return array_map('intval', $collection->getAllIds());
    }

    /**
     * Removes the expired special prices and returns the IDs of the updated products.
     *
     * @param bool $dryRun When true, only reports what would change
     * @return int[]
     */
    public function execute(bool $dryRun = false): array
    {
        $productIds = $this->findExpiredProductIds();
        if ($dryRun || !$productIds) {
            return $productIds;
        }

        // Same bulk update the admin "Update attributes" action uses (one query per attribute, not per product)
        $this->productAction->updateAttributes(
            $productIds,
            array_fill_keys(self::ATTRIBUTES_TO_CLEAR, null),
            Store::DEFAULT_STORE_ID
        );

        // updateAttributes() does not touch the price index, so refresh it for these products
        // (skipped automatically when the indexer is set to "Update by Schedule"; cron does it then)
        $this->priceIndexer->reindexList($productIds);

        // Clear the cached product pages so shoppers see the normal price straight away
        $this->cacheContext->registerEntities(Product::CACHE_TAG, $productIds);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);

        return $productIds;
    }
}
