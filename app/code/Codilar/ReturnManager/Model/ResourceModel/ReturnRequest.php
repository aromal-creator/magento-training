<?php

namespace Codilar\ReturnManager\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Reads and writes return requests in the codilar_return_request table.
 */
class ReturnRequest extends AbstractDb
{
    public const TABLE_NAME = 'codilar_return_request';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }
}
