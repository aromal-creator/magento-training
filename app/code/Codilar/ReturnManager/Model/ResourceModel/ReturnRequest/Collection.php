<?php

namespace Codilar\ReturnManager\Model\ResourceModel\ReturnRequest;

use Codilar\ReturnManager\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use Codilar\ReturnManager\Model\ReturnRequest;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * A list of return requests.
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(ReturnRequest::class, ReturnRequestResource::class);
    }
}
