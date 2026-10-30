<?php

namespace Codilar\StoreCreator\Setup\Patch\Data;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\NonTransactionableInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\WebsiteFactory;
use Magento\UrlRewrite\Model\UrlPersistInterface;

/**
 * Creates a "Codilar Store" (store group) with a "Codilar English" store view
 * under the Main Website.
 *
 * Non-transactional because the store_add event creates sequence tables (DDL).
 */
class CreateCodilarStore implements
    DataPatchInterface,
    PatchRevertableInterface,
    NonTransactionableInterface
{
    private const WEBSITE_CODE = 'base';
    private const GROUP_CODE = 'codilar_store';
    private const STORE_CODE = 'codilar_en';
    private const ROOT_CATEGORY_ID = 2;

    private ModuleDataSetupInterface $moduleDataSetup;
    private WebsiteFactory $websiteFactory;
    private WebsiteResource $websiteResource;
    private GroupFactory $groupFactory;
    private GroupResource $groupResource;
    private StoreFactory $storeFactory;
    private StoreResource $storeResource;
    private ManagerInterface $eventManager;
    private CategoryFactory $categoryFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator;
    private ProductUrlRewriteGenerator $productUrlRewriteGenerator;
    private UrlPersistInterface $urlPersist;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WebsiteFactory $websiteFactory,
        WebsiteResource $websiteResource,
        GroupFactory $groupFactory,
        GroupResource $groupResource,
        StoreFactory $storeFactory,
        StoreResource $storeResource,
        ManagerInterface $eventManager,
        CategoryFactory $categoryFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        ProductUrlRewriteGenerator $productUrlRewriteGenerator,
        UrlPersistInterface $urlPersist
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->websiteFactory = $websiteFactory;
        $this->websiteResource = $websiteResource;
        $this->groupFactory = $groupFactory;
        $this->groupResource = $groupResource;
        $this->storeFactory = $storeFactory;
        $this->storeResource = $storeResource;
        $this->eventManager = $eventManager;
        $this->categoryFactory = $categoryFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->productUrlRewriteGenerator = $productUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $website = $this->websiteFactory->create();
        $this->websiteResource->load($website, self::WEBSITE_CODE, 'code');

        // 1. Store (group): holds the root category
        $group = $this->groupFactory->create();
        $this->groupResource->load($group, self::GROUP_CODE, 'code');
        if (!$group->getId()) {
            $group->setWebsiteId((int) $website->getId());
            $group->setCode(self::GROUP_CODE);
            $group->setName('Codilar Store');
            $group->setRootCategoryId(self::ROOT_CATEGORY_ID);
            $this->groupResource->save($group);
        }

        // 2. Store view: what customers actually browse
        $store = $this->storeFactory->create();
        $this->storeResource->load($store, self::STORE_CODE, 'code');
        if (!$store->getId()) {
            $store->setCode(self::STORE_CODE);
            $store->setName('Codilar English');
            $store->setWebsiteId((int) $website->getId());
            $store->setGroupId((int) $group->getId());
            $store->setSortOrder(10);
            $store->setIsActive(1);
            $this->storeResource->save($store);

            // Creates the sequence tables and URL rewrites for the new store view
            $this->eventManager->dispatch('store_add', ['store' => $store]);

            $this->generateUrlRewrites($store);
        }

        // 3. Make the view the group's default view
        $group->setDefaultStoreId((int) $store->getId());
        $this->groupResource->save($group);

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    
    /**
     * Creates category and product URLs (e.g. shoes.html) for the new store view.
     * The admin does this through an adminhtml-only plugin, which doesn't run during setup:upgrade.
     */
    private function generateUrlRewrites(Store $store): void
    {
        $storeId = (int) $store->getId();

        $categories = $this->categoryFactory->create()
            ->getCategories(self::ROOT_CATEGORY_ID, 1, false, true, false);
        foreach ($categories as $category) {
            $category->setStoreId($storeId);
            $this->urlPersist->replace($this->categoryUrlRewriteGenerator->generate($category));
        }

        $products = $this->productCollectionFactory->create()
            ->addCategoryIds()
            ->addAttributeToSelect(['name', 'url_path', 'url_key', 'visibility'])
            ->addStoreFilter($storeId);
        foreach ($products as $product) {
            $product->setStoreId($storeId);
            $this->urlPersist->replace($this->productUrlRewriteGenerator->generate($product));
        }
    }

    public function revert()
    {
        $store = $this->storeFactory->create();
        $this->storeResource->load($store, self::STORE_CODE, 'code');
        if ($store->getId()) {
            $this->storeResource->delete($store);
        }

        $group = $this->groupFactory->create();
        $this->groupResource->load($group, self::GROUP_CODE, 'code');
        if ($group->getId()) {
            $this->groupResource->delete($group);
        }
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
