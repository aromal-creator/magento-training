<?php
/**
 * Codilar_SneakerDrop - limited-edition drops: max N pairs per customer, counting
 * past orders and the cart (DI preference example).
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_SneakerDrop', __DIR__);
