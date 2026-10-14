<?php
namespace Codilar\StudentRegistration\Controller\Index;

use Codilar\StudentRegistration\Model\StudentFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Save extends Action implements HttpPostActionInterface
{
    private $studentFactory;

    public function __construct(
        Context $context,
        StudentFactory $studentFactory
    ) {
        parent::__construct($context);
        $this->studentFactory = $studentFactory;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!is_array($data) || empty($data)) {
            $this->messageManager->addErrorMessage(
                __('Please submit the registration form.')
            );

            return $this->resultRedirectFactory
                ->create()
                ->setPath('studentregistration/index/index');
        }

        $name = trim((string)($data['student_name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $course = trim((string)($data['course'] ?? ''));

        $allowedCourses = ['Magento', 'PHP', 'Laravel'];

        if (
            $name === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $phone === '' ||
            !in_array($course, $allowedCourses, true)
        ) {
            $this->messageManager->addErrorMessage(
                __('Please enter valid student details.')
            );
        } else {
            try {
                $student = $this->studentFactory->create();

                $student->setData([
                    'student_name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'course' => $course
                ]);

                // Model delegates database persistence to Resource Model.
                $student->save();

                $this->messageManager->addSuccessMessage(
                    __('Student registration saved successfully.')
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Unable to save registration. Please check the Magento logs.')
                );
            }
        }

        return $this->resultRedirectFactory
            ->create()
            ->setPath('studentregistration/index/index');
    }
}
