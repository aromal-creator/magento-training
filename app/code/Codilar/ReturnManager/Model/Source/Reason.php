<?php

namespace Codilar\ReturnManager\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options for the "Return Reason" dropdown and grid filter.
 */
class Reason implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'damaged', 'label' => __('Damaged Item')],
            ['value' => 'wrong_item', 'label' => __('Wrong Item Received')],
            ['value' => 'not_as_described', 'label' => __('Not as Described')],
            ['value' => 'size_issue', 'label' => __('Size / Fit Issue')],
            ['value' => 'other', 'label' => __('Other')],
        ];
    }
}
