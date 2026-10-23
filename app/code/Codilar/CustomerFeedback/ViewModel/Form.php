<?php
namespace Codilar\CustomerFeedback\ViewModel;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Supplies default form values: data from a failed submission first,
 * otherwise the logged-in customer's name and email.
 */
class Form implements ArgumentInterface
{
    private DataPersistorInterface $dataPersistor;
    private CustomerSession $customerSession;
    private ?array $values = null;

    public function __construct(
        DataPersistorInterface $dataPersistor,
        CustomerSession $customerSession
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->customerSession = $customerSession;
    }

    public function getValue(string $field): string
    {
        if ($this->values === null) {
            $this->values = (array)$this->dataPersistor->get('customer_feedback');
            $this->dataPersistor->clear('customer_feedback');

            if (!$this->values && $this->customerSession->isLoggedIn()) {
                $customer = $this->customerSession->getCustomer();
                $this->values = [
                    'customer_name' => $customer->getName(),
                    'email' => $customer->getEmail(),
                ];
            }
        }

        return (string)($this->values[$field] ?? '');
    }
}
