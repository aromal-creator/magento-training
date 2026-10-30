<?php

namespace Codilar\ReturnManager\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options for the "Return Status" dropdown and grid filter.
 */
class Status implements OptionSourceInterface
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const REFUNDED = 'refunded';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::PENDING, 'label' => __('Pending')],
            ['value' => self::APPROVED, 'label' => __('Approved')],
            ['value' => self::REJECTED, 'label' => __('Rejected')],
            ['value' => self::REFUNDED, 'label' => __('Refunded')],
        ];
    }
}
