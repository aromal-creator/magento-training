<?php
declare(strict_types=1);

namespace Codilar\CartQuantityPlugin\Plugin;

use Codilar\CartQuantityPlugin\Model\QtyLimit;
use Magento\Checkout\Controller\Cart\UpdateItemQty;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * AROUND plugin on the cart page's qty check (checkout/cart/updateItemQty).
 *
 * Before "Update Shopping Cart" saves anything, the cart page JS asks this
 * controller whether the new quantities are OK. If one is over the limit we
 * answer with an error ourselves and skip the original controller; Magento's
 * JS then shows it in an alert and resets that qty box.
 * (The save step, updatePost, hides errors because it expects this check to show them.)
 */
class CartPageQtyCheck
{
    /**
     * @var QtyLimit
     */
    private $qtyLimit;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param QtyLimit $qtyLimit
     * @param CheckoutSession $checkoutSession
     * @param Json $json
     */
    public function __construct(QtyLimit $qtyLimit, CheckoutSession $checkoutSession, Json $json)
    {
        $this->qtyLimit = $qtyLimit;
        $this->checkoutSession = $checkoutSession;
        $this->json = $json;
    }

    /**
     * @param UpdateItemQty $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(UpdateItemQty $subject, callable $proceed)
    {
        $changes = $this->qtyLimit->toChanges($subject->getRequest()->getParam('cart'));
        $violation = $changes
            ? $this->qtyLimit->findViolation($this->checkoutSession->getQuote(), $changes)
            : null;

        if ($violation === null) {
            return $proceed();
        }

        // Same response shape as the core controller: error_message holds a JSON list of
        // {error, itemId}; the JS uses itemId to put that qty box back to its old value.
        $errors = [];
        foreach ($changes as $itemId => $qty) {
            $errors[] = ['error' => (string)$this->qtyLimit->message($violation), 'itemId' => $itemId];
        }

        $response = $subject->getResponse();
        $response->representJson($this->json->serialize([
            'success' => false,
            'error_message' => $this->json->serialize($errors),
        ]));

        return $response;
    }
}
