<?php

namespace Codilar\ProductOptions\Setup\Patch\Data;

use Codilar\ProductOptions\Setup\AbstractAddCustomOptions;

/**
 * Adds a required "Size" dropdown to the "Walkaroo" product.
 * Larger sizes cost a little more.
 */
class AddWalkarooSizeOption extends AbstractAddCustomOptions
{
    protected function getSku(): string
    {
        return 'Walkaroo';
    }

    protected function getOptions(): array
    {
        $sizes = [6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 50, 12 => 50];

        $values = [];
        $sort = 1;
        foreach ($sizes as $size => $extra) {
            $values[] = [
                'title' => 'UK ' . $size,
                'price' => $extra,
                'price_type' => 'fixed',
                'sku' => 'uk' . $size,
                'sort_order' => $sort++,
            ];
        }

        return [
            [
                'title' => 'Size',
                'type' => 'drop_down',
                'is_require' => 1,
                'sort_order' => 1,
                'values' => $values,
            ],
        ];
    }
}
