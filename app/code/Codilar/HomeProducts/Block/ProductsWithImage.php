<?php

namespace Codilar\HomeProducts\Block;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Block\Product\ProductsList;

/**
 * Home page product grid that skips products without a listing image.
 *
 * The filter is applied in getBaseCollection() because plugins on createCollection()
 * (e.g. Magento_ConfigurableProduct) may rebuild the collection from it.
 */
class ProductsWithImage extends ProductsList
{
    public function getBaseCollection(): Collection
    {
        $collection = parent::getBaseCollection();
        $collection->addAttributeToFilter('small_image', ['notnull' => true])
            ->addAttributeToFilter('small_image', ['neq' => 'no_selection']);

        return $collection;
    }
}
