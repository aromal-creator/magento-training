<?php

namespace Codilar\ProductCreator\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductRecord extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('codilar_product_record', 'entity_id');
    }
}
