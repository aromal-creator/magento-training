<?php
/**
 * Codilar_ContactValidation - stricter Contact Us validation by replacing a
 * PRIVATE vendor method through a DI preference.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_ContactValidation', __DIR__);
