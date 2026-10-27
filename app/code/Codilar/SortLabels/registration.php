<?php
/**
 * Codilar_SortLabels - customer-friendly "Sort By" labels on category pages by
 * replacing the PRIVATE vendor method Toolbar::loadAvailableOrders() (preference only).
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_SortLabels', __DIR__);
