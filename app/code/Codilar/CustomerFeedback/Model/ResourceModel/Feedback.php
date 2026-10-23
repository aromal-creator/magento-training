<?php
namespace Codilar\CustomerFeedback\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Feedback extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(
            'codilar_customer_feedback',
            'feedback_id'
        );
    }
}
