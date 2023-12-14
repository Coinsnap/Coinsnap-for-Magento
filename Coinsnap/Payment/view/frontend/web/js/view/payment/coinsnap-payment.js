define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
],function(Component,renderList){
    'use strict';
    renderList.push({
        type : 'coinsnap_payment',
        component : 'Coinsnap_Payment/js/view/payment/method-renderer/coinsnap-method'
    });

    return Component.extend({});
})
