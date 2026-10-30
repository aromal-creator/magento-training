<?php

namespace Codilar\CategoryAttribute\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Setup\Patch\Data\InstallDefaultCategories;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Adds a "Subtitle" text attribute to categories.
 */
class AddCategorySubtitleAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private const ATTRIBUTE_CODE = 'category_subtitle';

    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(Category::ENTITY, self::ATTRIBUTE_CODE, [
            'type' => 'varchar',
            'label' => 'Subtitle',
            'input' => 'text',
            'global' => Attribute::SCOPE_STORE,
            'group' => 'General Information',
            'sort_order' => 15,
            'required' => false,
            'user_defined' => true,
            'visible' => true,
        ]);

        return $this;
    }

    public function revert()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Category::ENTITY, self::ATTRIBUTE_CODE);
    }

    /**
     * The category entity and its "General Information" group must exist first.
     */
    public static function getDependencies()
    {
        return [InstallDefaultCategories::class];
    }

    public function getAliases()
    {
        return [];
    }
}
