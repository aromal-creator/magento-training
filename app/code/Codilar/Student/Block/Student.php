<?php

namespace Codilar\Student\Block;
 
use Magento\Framework\View\Element\Template;
use Codilar\Student\Model\Student as StudentModel;

class Student extends Template
{
    private $studentModel;

    public function __construct(
        Template\Context $context,
        StudentModel $studentModel,
        array $data = []
    ) {
        $this->studentModel = $studentModel;

        parent::__construct($context, $data);
    }

    public function getStudentName()
    {
        return $this->studentModel->getName();
    }

    public function getCourse()
    {
        return $this->studentModel->getCourse();
    }
}
