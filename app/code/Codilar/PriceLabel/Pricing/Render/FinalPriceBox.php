<?php
declare(strict_types=1);

namespace Codilar\PriceLabel\Pricing\Render;

use Magento\Catalog\Model\Product\Pricing\Renderer\SalableResolverInterface;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\View\Element\Template\Context;

/**
 * Replaces Magento's product price box on the storefront (see etc/frontend/di.xml).
 *
 * Everything is rendered by the core class as usual; wrapResult() then adds,
 * under the price:
 *   - "You save ₹X (Y%)" when the final price is lower than the regular price
 *   - "Inclusive of all taxes"
 */
class FinalPriceBox extends \Magento\Catalog\Pricing\Render\FinalPriceBox
{
    private PriceCurrencyInterface $priceCurrency;

    /**
     * Same arguments as the core class, plus the currency formatter.
     */
    public function __construct(
        Context $context,
        SaleableInterface $saleableItem,
        PriceInterface $price,
        RendererPool $rendererPool,
        PriceCurrencyInterface $priceCurrency,
        array $data = [],
        ?SalableResolverInterface $salableResolver = null,
        ?MinimalPriceCalculatorInterface $minimalPriceCalculator = null
    ) {
        parent::__construct(
            $context,
            $saleableItem,
            $price,
            $rendererPool,
            $data,
            $salableResolver,
            $minimalPriceCalculator
        );
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritdoc
     */
    protected function wrapResult($html)
    {
        return parent::wrapResult($html) . $this->getPriceNoteHtml();
    }

    /**
     * Only the main "final_price" box gets the note (not tier/minimal price boxes).
     *
     * @return string
     */
    private function getPriceNoteHtml(): string
    {
        if ($this->getPrice()->getPriceCode() !== FinalPrice::PRICE_CODE) {
            return '';
        }

        $html = '';
        $regular = (float)$this->getPriceType(RegularPrice::PRICE_CODE)->getAmount()->getValue();
        $final = (float)$this->getPriceType(FinalPrice::PRICE_CODE)->getAmount()->getValue();

        if ($regular > 0 && $final < $regular) {
            $saving = $regular - $final;
            $html .= '<div class="codilar-price-saving" style="color:#1a7f37;font-weight:600;">'
                . $this->escapeHtml(__(
                    'You save %1 (%2%)',
                    $this->priceCurrency->format($saving, false),
                    (int)round($saving / $regular * 100)
                ))
                . '</div>';
        }

        $html .= '<div class="codilar-price-tax-note" style="font-size:12px;color:#666;">'
            . $this->escapeHtml(__('Inclusive of all taxes'))
            . '</div>';

        return $html;
    }
}
