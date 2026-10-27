<?php
declare(strict_types=1);

namespace Codilar\HotDeal\Model;

/**
 * Replaces the core product model on the storefront (see etc/frontend/di.xml).
 *
 * Extends the original so every other method keeps its core behaviour; only
 * getName() changes: products with an active special price lower than their
 * regular price are shown as "🔥 Hot Deal - <name>".
 */
class Product extends \Magento\Catalog\Model\Product
{
    private const LABEL = '🔥 Hot Deal - ';

    /**
     * @return string|null
     */
    public function getName()
    {
        $name = parent::getName();

        if ($name === null || !$this->isHotDeal()) {
            return $name;
        }

        return self::LABEL . $name;
    }

    /**
     * Special price is set, lower than the regular price, and today is inside
     * the optional "Special Price From / To" dates.
     *
     * @return bool
     */
    private function isHotDeal(): bool
    {
        $specialPrice = $this->getData('special_price');
        $price = $this->getData('price');

        if (!is_numeric($specialPrice) || !is_numeric($price) || (float)$specialPrice >= (float)$price) {
            return false;
        }

        $today = date('Y-m-d');
        $from = $this->getData('special_from_date');
        $to = $this->getData('special_to_date');

        if ($from && substr((string)$from, 0, 10) > $today) {
            return false;
        }
        if ($to && substr((string)$to, 0, 10) < $today) {
            return false;
        }

        return true;
    }
}
