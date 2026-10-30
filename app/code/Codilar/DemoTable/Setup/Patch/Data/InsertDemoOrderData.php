<?php

namespace Codilar\DemoTable\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Inserts sample orders into codilar_demo_order, linked to the demo rows by email.
 */
class InsertDemoOrderData implements DataPatchInterface, PatchRevertableInterface
{
    private const TABLE_NAME = 'codilar_demo_order';
    private const DEFAULT_STORE_ID = 1;

    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();

        // email => entity_id, so the foreign key gets real parent IDs
        $demoIds = $connection->fetchPairs(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('codilar_demo_table'), ['email', 'entity_id'])
        );

        $orders = [
            ['email' => 'aromal.demo@example.com', 'order_number' => 'DEMO-1001', 'amount' => 1499.00],
            ['email' => 'aromal.demo@example.com', 'order_number' => 'DEMO-1002', 'amount' => 299.50],
            ['email' => 'rahul.demo@example.com', 'order_number' => 'DEMO-1003', 'amount' => 999.00],
        ];

        $rows = [];
        foreach ($orders as $order) {
            if (!isset($demoIds[$order['email']])) {
                continue;
            }
            $rows[] = [
                'demo_id' => (int) $demoIds[$order['email']],
                'store_id' => self::DEFAULT_STORE_ID,
                'order_number' => $order['order_number'],
                'amount' => $order['amount'],
            ];
        }

        if ($rows) {
            $connection->insertOnDuplicate(
                $this->moduleDataSetup->getTable(self::TABLE_NAME),
                $rows,
                ['demo_id', 'store_id', 'amount']
            );
        }

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable(self::TABLE_NAME),
            ['order_number LIKE ?' => 'DEMO-%']
        );
    }

    /**
     * The parent rows must exist before orders can point to them.
     */
    public static function getDependencies()
    {
        return [InsertDemoData::class];
    }

    public function getAliases()
    {
        return [];
    }
}
