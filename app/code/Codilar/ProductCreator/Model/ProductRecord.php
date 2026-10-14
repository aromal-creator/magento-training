<?php

namespace Codilar\ProductCreator\Model;

use Magento\Framework\Model\AbstractModel;

class ProductRecord extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(
            \Codilar\ProductCreator\Model\ResourceModel\ProductRecord::class
        );
    }
}
