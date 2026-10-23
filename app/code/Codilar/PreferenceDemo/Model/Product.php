<?php
declare(strict_types=1);

namespace Codilar\PreferenceDemo\Model;

/**
 * Replaces the core product model on the storefront (see etc/frontend/di.xml).
 *
 * Extends the original so every other method keeps its core behaviour;
 * only getName() is changed.
 */
class Product extends \Magento\Catalog\Model\Product
{
    private const NAME_SUFFIX = ' - Codilar';

    /**
     * @return string|null
     */
    public function getName()
    {
        $name = parent::getName();

        return $name === null ? null : $name . self::NAME_SUFFIX;
    }
}
