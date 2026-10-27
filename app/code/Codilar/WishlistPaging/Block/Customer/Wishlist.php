<?php
declare(strict_types=1);

namespace Codilar\WishlistPaging\Block\Customer;

/**
 * Replaces Magento's "My Wish List" block (see etc/frontend/di.xml) to replace
 * its PRIVATE method paginateCollection() from
 * vendor/magento/module-wishlist/Block/Customer/Wishlist.php.
 *
 *  1. paginateCollection() is declared again here as PRIVATE (same name).
 *  2. getWishlistItems() (PUBLIC, the only caller, 7 lines) is overridden with the
 *     core code, so that inside THIS class $this->paginateCollection() calls OUR
 *     private method. parent::getWishlistItems() is not called: it would use the
 *     core private method.
 *
 * Why: the core method passes ?limit= and ?p= from the URL straight to the
 * database, so /wishlist/?limit=1000000 tries to load a million rows.
 */
class Wishlist extends \Magento\Wishlist\Block\Customer\Wishlist
{
    private const ALLOWED_LIMITS = [10, 20, 50];
    private const DEFAULT_LIMIT = 10;

    /**
     * Core code, unchanged; paginateCollection() resolves to ours.
     *
     * @inheritdoc
     */
    public function getWishlistItems()
    {
        if ($this->_collection === null) {
            $this->_collection = $this->_createWishlistItemCollection();
            $this->_prepareCollection($this->_collection);
            $this->paginateCollection();
        }

        return $this->_collection;
    }

    /**
     * OUR paginateCollection(), replacing the core PRIVATE method of the same name.
     *
     * Core: page size and page number taken from the URL as they are (default 10, 1).
     * Ours: page size only 10, 20 or 50 (anything else -> 10); page a whole number >= 1.
     *
     * @return void
     */
    private function paginateCollection()
    {
        $limit = (int)$this->getRequest()->getParam('limit', self::DEFAULT_LIMIT);
        if (!in_array($limit, self::ALLOWED_LIMITS, true)) {
            $limit = self::DEFAULT_LIMIT;
        }

        $page = max(1, (int)$this->getRequest()->getParam('p', 1));

        $this->_collection
            ->setPageSize($limit)
            ->setCurPage($page);
    }
}
