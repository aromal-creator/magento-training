<?php

namespace Codilar\StoreConfigCache\Block\Adminhtml\System\Config;

use Codilar\StoreConfigCache\Model\StoreConfigCache;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Replaces the normal input of the "Cached Values" field with the list of paths and a Clear button.
 */
class ClearButton extends Field
{
    /** @var string */
    protected $_template = 'Codilar_StoreConfigCache::system/config/clear_button.phtml';

    private StoreConfigCache $storeConfigCache;

    public function __construct(Context $context, StoreConfigCache $storeConfigCache, array $data = [])
    {
        $this->storeConfigCache = $storeConfigCache;
        parent::__construct($context, $data);
    }

    /**
     * No "Use system value" checkbox or scope label: this field stores nothing.
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getClearUrl(): string
    {
        return $this->getUrl('codilarstoreconfig/cache/clear');
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->storeConfigCache->getPaths();
    }
}
