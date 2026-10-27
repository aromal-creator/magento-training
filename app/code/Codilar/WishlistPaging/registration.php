<?php
/**
 * Codilar_WishlistPaging - safe pagination on "My Wish List" by replacing the
 * PRIVATE vendor method Wishlist::paginateCollection() (preference only).
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Codilar_WishlistPaging', __DIR__);
