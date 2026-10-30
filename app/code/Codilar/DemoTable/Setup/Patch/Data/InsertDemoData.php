<?php

namespace Codilar\DemoTable\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Inserts sample rows into codilar_demo_table.
 */
class InsertDemoData implements DataPatchInterface, PatchRevertableInterface
{
    private const TABLE_NAME = 'codilar_demo_table';

    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $rows = [
            [
                'name' => 'Aromal',
                'email' => 'aromal.demo@example.com',
                'description' => 'First demo row',
                'is_active' => 1,
            ],
            [
                'name' => 'Rahul',
                'email' => 'rahul.demo@example.com',
                'description' => 'Second demo row',
                'is_active' => 1,
            ],
            [
                'name' => 'Priya',
                'email' => 'priya.demo@example.com',
                'description' => 'Inactive demo row',
                'is_active' => 0,
            ],
        ];

        // email is unique, so re-running the insert updates the rows instead of failing
        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable(self::TABLE_NAME),
            $rows,
            ['name', 'description', 'is_active']
        );

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable(self::TABLE_NAME),
            ['email LIKE ?' => '%.demo@example.com']
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
