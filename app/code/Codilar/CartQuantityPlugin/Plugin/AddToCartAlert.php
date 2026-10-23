<?php
declare(strict_types=1);

namespace Codilar\CartQuantityPlugin\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Controller\Cart\Add;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * AFTER plugin: adds a "codilar_alert" text to the AJAX "Add to Cart" response.
 * view/frontend/web/js/add-to-cart-alert.js shows it in a Magento alert popup.
 *
 * Registered on two classes in etc/frontend/di.xml:
 *   1. afterAddProduct() on Cart       - remembers what was added (only runs on success)
 *   2. afterExecute()    on Add action - writes the alert into the JSON response
 * Plugins are shared instances, so both methods see the same $added value
 * within one request.
 */
class AddToCartAlert
{
    /**
     * @var Json
     */
    private $json;

    /**
     * @var array|null ['name' => string, 'qty' => float] of the last product added
     */
    private $added;

    /**
     * @param Json $json
     */
    public function __construct(Json $json)
    {
        $this->json = $json;
    }

    /**
     * @param Cart $subject
     * @param Cart $result
     * @param int|Product $productInfo
     * @return Cart
     */
    public function afterAddProduct(Cart $subject, $result, $productInfo)
    {
        $productId = (int)($productInfo instanceof Product ? $productInfo->getId() : $productInfo);

        foreach ($subject->getQuote()->getAllVisibleItems() as $item) {
            if ((int)$item->getProductId() === $productId) {
                $this->added = ['name' => (string)$item->getName(), 'qty' => (float)$item->getQty()];
            }
        }

        return $result;
    }

    /**
     * @param Add $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(Add $subject, $result)
    {
        if ($this->added === null
            || !$subject->getRequest()->isAjax()
            || !$result instanceof HttpResponse
        ) {
            return $result;
        }

        try {
            $data = $this->json->unserialize((string)$result->getBody() ?: '{}');
        } catch (\InvalidArgumentException $e) {
            return $result;
        }

        if (!is_array($data) || isset($data['backUrl'])) {
            return $result;
        }

        $data['codilar_alert'] = (string)__(
            '%1 (qty in cart: %2) was added to your cart successfully.',
            $this->added['name'],
            $this->added['qty']
        );
        $result->representJson($this->json->serialize($data));

        return $result;
    }
}
