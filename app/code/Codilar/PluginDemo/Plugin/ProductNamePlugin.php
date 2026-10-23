<?php
declare(strict_types=1);

namespace Codilar\PluginDemo\Plugin;

use Magento\Catalog\Model\Product;

/**
 * AFTER plugin example.
 *
 * Runs after Product::getName(). Receives what the original method returned
 * in $result; whatever this method returns becomes the new return value.
 */
class ProductNamePlugin
{
    /**
     * @param Product $subject The product getName() was called on
     * @param string|null $result The original name
     * @return string|null
     */
    public function afterGetName(Product $subject, $result)
    {
        return $result === null ? null : $result . ' (via plugin)';
    }
}
