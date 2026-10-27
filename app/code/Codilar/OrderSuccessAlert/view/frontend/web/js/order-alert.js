/**
 * Opens a Magento alert popup with the text from the order success observer.
 */
define([
    'underscore',
    'Magento_Ui/js/modal/alert'
], function (_, alert) {
    'use strict';

    return function (config) {
        alert({
            title: config.title,
            content: _.escape(config.text)
        });
    };
});
