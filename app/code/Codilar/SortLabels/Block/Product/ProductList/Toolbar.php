<?php
declare(strict_types=1);

namespace Codilar\SortLabels\Block\Product\ProductList;

/**
 * Replaces Magento's category toolbar (see etc/frontend/di.xml) to replace its
 * PRIVATE method loadAvailableOrders() from
 * vendor/magento/module-catalog/Block/Product/ProductList/Toolbar.php.
 *
 *  1. loadAvailableOrders() is declared again here as PRIVATE (same name).
 *  2. The 4 PUBLIC methods that call it are overridden with the core code
 *     (3-6 lines each), so that inside THIS class $this->loadAvailableOrders()
 *     calls OUR private method. Their parent:: versions are not called, because
 *     they would use the core private method.
 */
class Toolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{
    /**
     * Sort option code => customer-friendly label.
     */
    private const LABELS = [
        'position' => 'Recommended',
        'name' => 'Name (A-Z)',
    ];

    /**
     * Core code, unchanged.
     *
     * @inheritdoc
     */
    public function getAvailableOrders()
    {
        $this->loadAvailableOrders();

        return $this->_availableOrder;
    }

    /**
     * Core code, unchanged.
     *
     * @inheritdoc
     */
    public function setDefaultOrder($field)
    {
        $this->loadAvailableOrders();
        if (isset($this->_availableOrder[$field])) {
            $this->_orderField = $field;
        }

        return $this;
    }

    /**
     * Core code, unchanged.
     *
     * @inheritdoc
     */
    public function addOrderToAvailableOrders($order, $value)
    {
        $this->loadAvailableOrders();
        $this->_availableOrder[$order] = $value;

        return $this;
    }

    /**
     * Core code, unchanged.
     *
     * @inheritdoc
     */
    public function removeOrderFromAvailableOrders($order)
    {
        $this->loadAvailableOrders();
        if (isset($this->_availableOrder[$order])) {
            unset($this->_availableOrder[$order]);
        }

        return $this;
    }

    /**
     * OUR loadAvailableOrders(), replacing the core PRIVATE method of the same name.
     *
     * Core: if no options are set yet, load them from the catalog config.
     * Ours: the same, then always apply the customer-friendly labels from LABELS,
     *       also when the options were set earlier through setAvailableOrders()
     *       (the category page does that before the toolbar renders).
     *
     * @return $this
     */
    private function loadAvailableOrders()
    {
        if ($this->_availableOrder === null) {
            $this->_availableOrder = $this->_catalogConfig->getAttributeUsedForSortByArray();
        }

        foreach (self::LABELS as $code => $label) {
            if (isset($this->_availableOrder[$code])) {
                $this->_availableOrder[$code] = __($label);
            }
        }

        return $this;
    }
}
