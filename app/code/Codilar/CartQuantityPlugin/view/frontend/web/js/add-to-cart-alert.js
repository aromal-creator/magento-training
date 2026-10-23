/**
 * Shows the "added to cart" alert that Codilar\CartQuantityPlugin\Plugin\AddToCartAlert
 * puts into the AJAX add-to-cart response (response.codilar_alert).
 */
define([
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, _, alert) {
    'use strict';

    $(document).on('ajax:addToCart', function (event, data) {
        var text = data && data.response && data.response.codilar_alert;

        if (text) {
            alert({
                title: $.mage.__('Cart'),
                content: _.escape(text)
            });
        }
    });
});
