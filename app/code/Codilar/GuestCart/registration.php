<?php
/**
 * Codilar_GuestCart — guests must sign in before adding to cart (DI preference example).
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_GuestCart', __DIR__);
