<?php
/**
 * Codilar_OrderCoupon — gives the customer a one-time 10% coupon after every order (observer example).
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_OrderCoupon', __DIR__);
