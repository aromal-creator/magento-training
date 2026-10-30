<?php

namespace Codilar\CategoryAttribute\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Updates the ISO3 code of the sample country "ZZ" from ZZZ to ZZA.
 */
class UpdateSampleCountry implements DataPatchInterface, PatchRevertableInterface
{
    private const COUNTRY_ID = 'ZZ';
    private const OLD_ISO3_CODE = 'ZZZ';
    private const NEW_ISO3_CODE = 'ZZA';

    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('directory_country'),
            ['iso3_code' => self::NEW_ISO3_CODE],
            ['country_id = ?' => self::COUNTRY_ID]
        );

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('directory_country'),
            ['iso3_code' => self::OLD_ISO3_CODE],
            ['country_id = ?' => self::COUNTRY_ID]
        );
    }

    /**
     * The country must exist before it can be updated.
     */
    public static function getDependencies()
    {
        return [AddSampleCountry::class];
    }

    public function getAliases()
    {
        return [];
    }
}
