<?php

namespace Codilar\CategoryAttribute\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Adds a sample country "ZZ" to directory_country.
 */
class AddSampleCountry implements DataPatchInterface, PatchRevertableInterface
{
    private const COUNTRY_ID = 'ZZ';

    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable('directory_country'),
            [
                'country_id' => self::COUNTRY_ID,
                'iso2_code' => 'ZZ',
                'iso3_code' => 'ZZZ',
            ]
        );

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable('directory_country'),
            ['country_id = ?' => self::COUNTRY_ID]
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
