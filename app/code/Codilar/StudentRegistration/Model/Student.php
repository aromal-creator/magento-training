<?php
namespace Codilar\StudentRegistration\Model;

use Magento\Framework\Model\AbstractModel;

class Student extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(
            \Codilar\StudentRegistration\Model\ResourceModel\Student::class
        );
    }
}
