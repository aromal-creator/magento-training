<?php
declare(strict_types=1);

namespace Codilar\PluginDemo\Plugin;

use Magento\Theme\Block\Html\Footer;

/**
 * AROUND plugin example.
 *
 * Wraps Footer::getCopyright(). $proceed() calls the original method (and any
 * other plugins after this one); code before/after it runs around the call.
 * Leaving $proceed() out would skip the original method completely.
 */
class FooterCopyrightPlugin
{
    /**
     * @param Footer $subject The footer block
     * @param callable $proceed Calls the original getCopyright()
     * @return string
     */
    public function aroundGetCopyright(Footer $subject, callable $proceed): string
    {
        $original = (string)$proceed();

        return $original . ' | Customised by Codilar (around plugin)';
    }
}
