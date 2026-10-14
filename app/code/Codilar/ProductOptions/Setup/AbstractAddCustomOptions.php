<?php

namespace Codilar\ProductOptions\Setup;

use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Base for patches that add priced custom options to one product.
 * Fixed = flat amount added to the price, Percent = % of the product price.
 */
abstract class AbstractAddCustomOptions implements DataPatchInterface
{
    private ProductRepositoryInterface $productRepository;
    private ProductCustomOptionInterfaceFactory $optionFactory;
    private ProductCustomOptionRepositoryInterface $optionRepository;
    private ModuleDataSetupInterface $moduleDataSetup;
    private State $appState;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductCustomOptionInterfaceFactory $optionFactory,
        ProductCustomOptionRepositoryInterface $optionRepository,
        ModuleDataSetupInterface $moduleDataSetup,
        State $appState
    ) {
        $this->productRepository = $productRepository;
        $this->optionFactory = $optionFactory;
        $this->optionRepository = $optionRepository;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->appState = $appState;
    }

    abstract protected function getSku(): string;

    abstract protected function getOptions(): array;

    public function apply()
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, [$this, 'addOptions']);

        return $this;
    }

    public function addOptions(): void
    {
        try {
            $product = $this->productRepository->get($this->getSku(), false, 0, true);
        } catch (NoSuchEntityException $e) {
            return;
        }

        $existingTitles = [];
        $hasRequired = false;
        foreach ($this->optionRepository->getProductOptions($product) as $existing) {
            $existingTitles[] = $existing->getTitle();
            $hasRequired = $hasRequired || $existing->getIsRequire();
        }

        foreach ($this->getOptions() as $data) {
            $hasRequired = $hasRequired || !empty($data['is_require']);
            if (in_array($data['title'], $existingTitles, true)) {
                continue;
            }
            $option = $this->optionFactory->create(['data' => $data]);
            $option->setProductSku($this->getSku());
            $this->optionRepository->save($option);
        }

        // Tell the catalog the product now has options; required ones make the
        // category-page "Add to Cart" open the product page to choose first
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('catalog_product_entity'),
            ['has_options' => 1, 'required_options' => (int)$hasRequired],
            ['entity_id = ?' => $product->getId()]
        );
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
