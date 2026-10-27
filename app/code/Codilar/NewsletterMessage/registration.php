<?php
/**
 * Codilar_NewsletterMessage - replaces the PRIVATE vendor method
 * NewAction::getSuccessMessage() using a DI preference only.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_NewsletterMessage', __DIR__);
