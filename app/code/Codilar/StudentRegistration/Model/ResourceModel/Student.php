<?php
namespace Codilar\StudentRegistration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Student extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(
            'codilar_student_registration',
            'student_id'
        );
    }
}
