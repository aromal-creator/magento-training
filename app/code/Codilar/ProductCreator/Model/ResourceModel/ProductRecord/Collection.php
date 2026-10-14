<?php

namespace Codilar\ProductCreator\Model\ResourceModel\ProductRecord;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Codilar\ProductCreator\Model\ProductRecord;
use Codilar\ProductCreator\Model\ResourceModel\ProductRecord
    as ProductRecordResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            ProductRecord::class,
            ProductRecordResource::class
        );
    }
}
