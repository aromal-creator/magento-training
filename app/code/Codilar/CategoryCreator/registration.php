<?php
/**
 * Codilar_CategoryCreator - study module: create a category through Magento's
 * core API and show which database tables it touches.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_CategoryCreator', __DIR__);
