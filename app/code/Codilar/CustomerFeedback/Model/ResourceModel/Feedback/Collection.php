<?php
namespace Codilar\CustomerFeedback\Model\ResourceModel\Feedback;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'feedback_id';

    protected function _construct()
    {
        $this->_init(
            \Codilar\CustomerFeedback\Model\Feedback::class,
            \Codilar\CustomerFeedback\Model\ResourceModel\Feedback::class
        );
    }
}
