<?php
/**
 * Codilar_CartQuantityPlugin — when a customer adds 1 of a product to the cart, add 2 instead.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_CartQuantityPlugin', __DIR__);
