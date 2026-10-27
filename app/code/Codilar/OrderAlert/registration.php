<?php
/**
 * Codilar_OrderAlert — flags orders above ₹10,000 with an admin comment and a log line (observer example).
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_OrderAlert', __DIR__);
