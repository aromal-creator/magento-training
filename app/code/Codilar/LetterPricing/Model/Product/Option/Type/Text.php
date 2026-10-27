<?php
declare(strict_types=1);

namespace Codilar\LetterPricing\Model\Product\Option\Type;

use Magento\Catalog\Model\Product\Option\Type\DefaultType;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\StringUtils;

/**
 * Replaces Magento's text custom option type (see etc/di.xml).
 *
 * For options listed in di.xml ("Initials on Shoe"), the price is charged per
 * letter instead of once:
 *
 *     price per letter = option price / max characters   (₹150 / 3 = ₹50)
 *     charged          = price per letter x letters typed (spaces not counted)
 *
 *     "R" = ₹50, "RK" = ₹100, "RKS" = ₹150
 *
 * Every other text option, and percent-priced options, keep the core behaviour.
 */
class Text extends \Magento\Catalog\Model\Product\Option\Type\Text
{
    /**
     * @var string[]
     */
    private array $perLetterOptionTitles;

    /**
     * Same arguments as the core class, plus the option titles from di.xml.
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper,
        StringUtils $string,
        array $data = [],
        array $perLetterOptionTitles = []
    ) {
        parent::__construct($checkoutSession, $scopeConfig, $escaper, $string, $data);
        $this->perLetterOptionTitles = array_values($perLetterOptionTitles);
    }

    /**
     * @inheritdoc
     */
    public function getOptionPrice($optionValue, $basePrice)
    {
        $option = $this->getOption();

        if (!$this->isPerLetterOption()) {
            return parent::getOptionPrice($optionValue, $basePrice);
        }

        $letters = mb_strlen((string)preg_replace('/\s+/u', '', (string)$optionValue));
        $pricePerLetter = (float)$option->getPrice() / (int)$option->getMaxCharacters();

        return round($pricePerLetter * $letters, 2);
    }

    /**
     * "Overriding" a private method.
     *
     * The core Text::validateUserValue() cleans the customer's text with its
     * PRIVATE method normalizeNewLineSymbols(). A private method cannot be
     * overridden, so this PUBLIC method that calls it is overridden instead,
     * and uses our own private normalizeInitials() for per-letter options:
     * "r k s" is stored as "RKS" (and is no longer rejected as "too long").
     *
     * Other text options go through the core method unchanged.
     *
     * @inheritdoc
     */
    public function validateUserValue($values)
    {
        if (!$this->isPerLetterOption()) {
            return parent::validateUserValue($values);
        }

        // Skip the core Text version (it would apply the old private cleanup)
        // and run its parent's checks directly.
        DefaultType::validateUserValue($values);

        $option = $this->getOption();
        $value = $this->normalizeInitials((string)$this->getUserValue());

        if ($value === '' && $option->getIsRequire() && !$this->getSkipCheckRequiredOption()) {
            $this->setIsValid(false);
            throw new LocalizedException(
                __("The product's required option(s) weren't entered. Make sure the options are entered and try again.")
            );
        }

        $maxCharacters = (int)$option->getMaxCharacters();
        if ($maxCharacters > 0 && mb_strlen($value) > $maxCharacters) {
            $this->setIsValid(false);
            throw new LocalizedException(__('The text is too long. Shorten the text and try again.'));
        }

        $this->setUserValue($value);

        return $this;
    }

    /**
     * Our replacement for the core private normalizeNewLineSymbols():
     * removes all spaces/new lines and upper-cases the initials.
     *
     * @param string $value
     * @return string
     */
    private function normalizeInitials(string $value): string
    {
        return mb_strtoupper((string)preg_replace('/\s+/u', '', $value));
    }

    /**
     * Fixed-price option listed in di.xml with a maximum length.
     *
     * @return bool
     */
    private function isPerLetterOption(): bool
    {
        $option = $this->getOption();

        return $option
            && (int)$option->getMaxCharacters() > 0
            && $option->getPriceType() !== Value::TYPE_PERCENT
            && in_array((string)$option->getTitle(), $this->perLetterOptionTitles, true);
    }
}
