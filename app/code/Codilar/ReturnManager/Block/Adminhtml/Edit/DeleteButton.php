<?php

namespace Codilar\ReturnManager\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * "Delete" button: shown only when editing an existing return request.
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $id = $this->getReturnRequestId();
        if (!$id) {
            return [];
        }

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            // {"data": {}} makes deleteConfirm send a POST with the form key
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s', {\"data\": {}})",
                __('Are you sure you want to delete this return request?'),
                $this->getUrl('*/*/delete', ['id' => $id])
            ),
            'sort_order' => 20,
        ];
    }
}
