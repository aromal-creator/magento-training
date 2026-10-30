<?php

namespace Codilar\ReturnManager\Block\Adminhtml\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * Shared helpers for the form's buttons.
 */
class GenericButton
{
    protected Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getReturnRequestId(): int
    {
        return (int) $this->context->getRequest()->getParam('id');
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
