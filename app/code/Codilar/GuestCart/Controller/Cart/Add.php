<?php
declare(strict_types=1);

namespace Codilar\GuestCart\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\AddProductToCart;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Replaces the core add-to-cart controller on the storefront (see etc/frontend/di.xml).
 *
 * When "Require Sign In to Add to Cart" is on and the visitor is a guest, the
 * product is NOT added: they get a "Please sign in" message and are sent to the
 * login page, and after logging in they come back to the page they were on.
 * Everyone else goes through the original controller (parent::execute()).
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{
    public const XML_PATH_REQUIRE_LOGIN = 'checkout/codilar_guest_cart/require_login';

    private CustomerSession $customerSession;
    private CustomerUrl $customerUrl;
    private Json $json;

    /**
     * Same arguments as the core controller, plus the three we need.
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        CustomerSession $customerSession,
        CustomerUrl $customerUrl,
        Json $json,
        ?RequestQuantityProcessor $quantityProcessor = null,
        ?AddProductToCart $addProductToCart = null
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository,
            $quantityProcessor,
            $addProductToCart
        );
        $this->customerSession = $customerSession;
        $this->customerUrl = $customerUrl;
        $this->json = $json;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->isLoginRequired() || $this->customerSession->isLoggedIn()) {
            return parent::execute();
        }

        // Where to come back to after logging in: the page the guest clicked from.
        $returnUrl = $this->_redirect->getRefererUrl() ?: $this->_url->getUrl('/');
        $this->customerSession->setBeforeAuthUrl($returnUrl);

        
        $this->messageManager->addNoticeMessage(__('Please sign in to add products to your cart.'));
        $loginUrl = $this->customerUrl->getLoginUrl();

        if ($this->getRequest()->isAjax()) {
            // The storefront add-to-cart JS redirects the browser to "backUrl".
            $this->getResponse()->representJson($this->json->serialize(['backUrl' => $loginUrl]));

            return $this->getResponse();
        }

        return $this->resultRedirectFactory->create()->setUrl($loginUrl);
    }

    /**
     * Admin toggle: Stores > Configuration > Sales > Checkout > Guest Add to Cart.
     *
     * @return bool
     */
    private function isLoginRequired(): bool
    {
        return $this->_scopeConfig->isSetFlag(self::XML_PATH_REQUIRE_LOGIN, ScopeInterface::SCOPE_STORE);
    }
}
