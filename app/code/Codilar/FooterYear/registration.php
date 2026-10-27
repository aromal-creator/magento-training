<?php
/**
 * Codilar_FooterYear - replaces the PRIVATE vendor method Footer::replaceCurrentYear()
 * using a DI preference only.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_FooterYear', __DIR__);
