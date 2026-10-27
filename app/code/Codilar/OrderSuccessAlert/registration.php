<?php
/**
 * Codilar_OrderSuccessAlert - shows "Thank you! Your order has been placed successfully."
 * on the checkout success page (event + observer example).
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_OrderSuccessAlert', __DIR__);
