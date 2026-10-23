<?php
declare(strict_types=1);

namespace Codilar\PluginDemo\Plugin;

use Magento\Framework\View\Page\Title;

/**
 * BEFORE plugin example.
 *
 * Runs before Title::set($title). Returning an array replaces the original
 * arguments (in order); returning null keeps them unchanged.
 */
class PageTitlePlugin
{
    /**
     * @param Title $subject The original object (not used here)
     * @param mixed $title The argument the caller passed to set()
     * @return array|null
     */
    public function beforeSet(Title $subject, $title): ?array
    {
        if (!is_string($title) || $title === '') {
            return null;
        }
        
        return ['[Plugin] ' . $title];
    }
}
