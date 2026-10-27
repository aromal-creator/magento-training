<?php
declare(strict_types=1);

namespace Codilar\SneakerDrop\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Creates the "Max Pairs Per Customer" product attribute (max_per_customer) and
 * adds it to the "Product Details" group of every attribute set.
 * Empty = no limit. Set it to 1 on the limited sneaker.
 */
class AddMaxPerCustomerAttribute implements DataPatchInterface
{
    public const ATTRIBUTE_CODE = 'max_per_customer';

    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup, EavSetupFactory $eavSetupFactory)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(Product::ENTITY, self::ATTRIBUTE_CODE, [
            'type' => 'int',
            'label' => 'Max Pairs Per Customer',
            'input' => 'text',
            'frontend_class' => 'validate-digits',
            'note' => 'Limited releases: most units one customer can ever buy (cart + past orders). Empty = no limit.',
            'global' => Attribute::SCOPE_GLOBAL,
            'required' => false,
            'user_defined' => true,
            'visible' => true,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'group' => 'Product Details',
            'sort_order' => 200,
        ]);

        return $this;
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
