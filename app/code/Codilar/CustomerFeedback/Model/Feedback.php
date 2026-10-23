<?php
namespace Codilar\CustomerFeedback\Model;

use Magento\Framework\Model\AbstractModel;

class Feedback extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(
            \Codilar\CustomerFeedback\Model\ResourceModel\Feedback::class
        );
    }
}
