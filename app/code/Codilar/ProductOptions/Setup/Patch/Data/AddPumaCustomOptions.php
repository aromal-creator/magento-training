<?php

namespace Codilar\ProductOptions\Setup\Patch\Data;

use Codilar\ProductOptions\Setup\AbstractAddCustomOptions;

/**
 * Adds Gift Wrap and Name Engraving options to the "Puma" product.
 */
class AddPumaCustomOptions extends AbstractAddCustomOptions
{
    protected function getSku(): string
    {
        return 'Puma';
    }

    protected function getOptions(): array
    {
        return [
            [
                'title' => 'Gift Wrap',
                'type' => 'drop_down',
                'is_require' => 0,
                'sort_order' => 1,
                'values' => [
                    ['title' => 'No Gift Wrap', 'price' => 0, 'price_type' => 'fixed', 'sku' => '', 'sort_order' => 1],
                    ['title' => 'Gift Wrap', 'price' => 99, 'price_type' => 'fixed', 'sku' => 'wrap', 'sort_order' => 2],
                    ['title' => 'Premium Gift Box', 'price' => 10, 'price_type' => 'percent', 'sku' => 'box', 'sort_order' => 3],
                ],
            ],
            [
                'title' => 'Name Engraving',
                'type' => 'field',
                'is_require' => 0,
                'sort_order' => 2,
                'price' => 149,
                'price_type' => 'fixed',
                'sku' => 'engrave',
                'max_characters' => 20,
            ],
        ];
    }
}
