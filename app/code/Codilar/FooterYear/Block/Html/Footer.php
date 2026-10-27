<?php
declare(strict_types=1);

namespace Codilar\FooterYear\Block\Html;

/**
 * Replaces Magento's footer block (see etc/frontend/di.xml) to replace its
 * PRIVATE method replaceCurrentYear() from
 * vendor/magento/module-theme/Block/Html/Footer.php.
 *
 *  1. replaceCurrentYear() is declared again here as PRIVATE (same name).
 *  2. getCopyright() (PUBLIC, 8 lines) is overridden with the core code, so that
 *     inside THIS class $this->replaceCurrentYear() calls OUR private method.
 *     parent::getCopyright() is not called: it would use the core private method.
 */
class Footer extends \Magento\Theme\Block\Html\Footer
{
    /**
     * Core getCopyright(), unchanged; replaceCurrentYear() resolves to ours.
     *
     * @inheritdoc
     */
    public function getCopyright()
    {
        if (!$this->_copyright) {
            $this->_copyright = $this->_scopeConfig->getValue(
                'design/footer/copyright',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        return $this->replaceCurrentYear((string)__($this->_copyright));
    }

    /**
     * OUR replaceCurrentYear(), replacing the core PRIVATE method of the same name.
     *
     * Core: only "{YYYY}" -> current year.
     * Ours: "{YYYY}" and the word "present" -> current year ("2013-present" -> "2013-2026"),
     *       "{STORE}" -> the store's name.
     *
     * @param string $text
     * @return string
     */
    private function replaceCurrentYear(string $text): string
    {
        $year = (new \DateTime())->format('Y');
        $storeName = (string)$this->_storeManager->getStore()->getFrontendName();

        return str_replace(['{YYYY}', 'present', '{STORE}'], [$year, $year, $storeName], $text);
    }
}
