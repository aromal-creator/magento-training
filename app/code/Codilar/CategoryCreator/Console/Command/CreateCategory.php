<?php
declare(strict_types=1);

namespace Codilar\CategoryCreator\Console\Command;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento codilar:category:create "Sneakers" [--parent=2] [--products=Redtape,Puma] [--inactive]
 *
 * Creates a category the way Magento itself does (CategoryRepositoryInterface::save),
 * optionally assigns products (CategoryLinkManagementInterface), then prints every
 * database row that belongs to the new category, table by table.
 */
class CreateCategory extends Command
{
    private CategoryInterfaceFactory $categoryFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryLinkManagementInterface $categoryLinkManagement;
    private ResourceConnection $resource;
    private State $appState;
    private StoreManagerInterface $storeManager;

    public function __construct(
        CategoryInterfaceFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
        ResourceConnection $resource,
        State $appState,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->resource = $resource;
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('codilar:category:create')
            ->setDescription('Create a category through the core API and show the affected tables')
            ->addArgument('name', InputArgument::REQUIRED, 'Category name')
            ->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Parent category id', '2')
            ->addOption('products', null, InputOption::VALUE_REQUIRED, 'Comma-separated SKUs to assign', '')
            ->addOption('inactive', null, InputOption::VALUE_NONE, 'Create the category disabled');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Category saves run as the admin would (needed for URL rewrites, indexers, etc.)
        return (int)$this->appState->emulateAreaCode(
            Area::AREA_ADMINHTML,
            fn () => $this->run_($input, $output)
        );
    }

    private function run_(InputInterface $input, OutputInterface $output): int
    {
        // Save at "All Store Views" (store 0), like the admin does for a new category:
        // values are stored once as defaults, and URL rewrites are made for every store view.
        $this->storeManager->setCurrentStore(Store::DEFAULT_STORE_ID);

        $parentId = (int)$input->getOption('parent');
        $parent = $this->categoryRepository->get($parentId);
        $before = (int)$parent->getChildrenCount();

        // STEP 1: build the category object (nothing is saved yet)
        /** @var CategoryInterface $category */
        $category = $this->categoryFactory->create();
        $category->setStoreId(Store::DEFAULT_STORE_ID);
        $category->setName((string)$input->getArgument('name'))
            ->setParentId($parentId)
            ->setIsActive(!$input->getOption('inactive'))
            ->setIncludeInMenu(true)
            ->setCustomAttributes([]);
        $category->setData('is_anchor', 1);
        $category->setData('description', 'Created by Codilar_CategoryCreator for study.');

        // STEP 2: save through the repository (this writes to the database)
        $category = $this->categoryRepository->save($category);
        $id = (int)$category->getId();
        $output->writeln(sprintf('<info>Created category #%d "%s" under #%d</info>', $id, $category->getName(), $parentId));

        // STEP 3 (optional): assign products
        $skus = array_filter(array_map('trim', explode(',', (string)$input->getOption('products'))));
        foreach ($skus as $position => $sku) {
            $this->categoryLinkManagement->assignProductToCategories(
                $sku,
                $this->mergeCategoryIds($sku, $id)
            );
            $output->writeln(sprintf('  assigned product %s', $sku));
        }

        $this->printAffectedTables($output, $id, $parentId, $before);

        return Command::SUCCESS;
    }

    /**
     * Keep the product's existing categories and add the new one.
     */
    private function mergeCategoryIds(string $sku, int $newId): array
    {
        $c = $this->resource->getConnection();
        $existing = $c->fetchCol(
            $c->select()
                ->from(['cp' => $this->resource->getTableName('catalog_category_product')], 'category_id')
                ->join(['e' => $this->resource->getTableName('catalog_product_entity')], 'e.entity_id = cp.product_id', [])
                ->where('e.sku = ?', $sku)
        );
        $existing[] = $newId;

        return array_values(array_unique(array_map('intval', $existing)));
    }

    /**
     * Print every row that belongs to the new category.
     */
    private function printAffectedTables(OutputInterface $output, int $id, int $parentId, int $before): void
    {
        $c = $this->resource->getConnection();
        $t = fn (string $name) => $this->resource->getTableName($name);
        $attr = $t('eav_attribute');

        $output->writeln("\n<comment>1) catalog_category_entity  (the category itself + tree position)</comment>");
        $row = $c->fetchRow($c->select()->from($t('catalog_category_entity'))->where('entity_id = ?', $id));
        $output->writeln('   ' . json_encode($row));
        $after = (int)$c->fetchOne($c->select()->from($t('catalog_category_entity'), 'children_count')->where('entity_id = ?', $parentId));
        $output->writeln(sprintf('   parent #%d children_count: %d -> %d (parent row UPDATED)', $parentId, $before, $after));

        foreach (['varchar', 'int', 'text', 'decimal', 'datetime'] as $type) {
            $rows = $c->fetchAll(
                $c->select()
                    ->from(['v' => $t('catalog_category_entity_' . $type)], ['store_id', 'value'])
                    ->join(['a' => $attr], 'a.attribute_id = v.attribute_id', ['attribute_code'])
                    ->where('v.entity_id = ?', $id)
            );
            $output->writeln(sprintf("\n<comment>2) catalog_category_entity_%s  (%d rows: EAV attribute values)</comment>", $type, count($rows)));
            foreach ($rows as $r) {
                $output->writeln(sprintf('   %-24s store %d = %s', $r['attribute_code'], $r['store_id'], $r['value']));
            }
        }

        $rows = $c->fetchAll($c->select()->from($t('url_rewrite'), ['store_id', 'request_path', 'target_path'])
            ->where('entity_type = ?', 'category')->where('entity_id = ?', $id));
        $output->writeln(sprintf("\n<comment>3) url_rewrite  (%d rows: storefront URLs)</comment>", count($rows)));
        foreach ($rows as $r) {
            $output->writeln(sprintf('   store %d  /%s -> %s', $r['store_id'], $r['request_path'], $r['target_path']));
        }

        $rows = $c->fetchAll($c->select()->from(['cp' => $t('catalog_category_product')], ['product_id', 'position'])
            ->join(['e' => $t('catalog_product_entity')], 'e.entity_id = cp.product_id', ['sku'])
            ->where('cp.category_id = ?', $id));
        $output->writeln(sprintf("\n<comment>4) catalog_category_product  (%d rows: product assignments)</comment>", count($rows)));
        foreach ($rows as $r) {
            $output->writeln(sprintf('   product #%d %s position %d', $r['product_id'], $r['sku'], $r['position']));
        }

        $n = (int)$c->fetchOne($c->select()->from($t('url_rewrite'), 'COUNT(*)')
            ->where('entity_type = ?', 'product')->where('metadata LIKE ?', '%"category_id":"' . $id . '"%'));
        $output->writeln(sprintf("\n<comment>5) url_rewrite (product URLs inside this category): %d rows</comment>", $n));
    }
}
