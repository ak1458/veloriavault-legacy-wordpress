/******/ (() => { // webpackBootstrap
/******/ 	"use strict";

;// CONCATENATED MODULE: external ["wp","plugins"]
const external_wp_plugins_namespaceObject = window["wp"]["plugins"];
;// CONCATENATED MODULE: external ["wp","element"]
const external_wp_element_namespaceObject = window["wp"]["element"];
;// CONCATENATED MODULE: external ["wp","data"]
const external_wp_data_namespaceObject = window["wp"]["data"];
;// CONCATENATED MODULE: external ["wc","wcBlocksData"]
const external_wc_wcBlocksData_namespaceObject = window["wc"]["wcBlocksData"];
;// CONCATENATED MODULE: ./includes/wc-blocks/assets/js/edit-link/index.js




var addEditLink = function addEditLink() {
  var cartItems = (0,external_wp_data_namespaceObject.useSelect)(function (select) {
    return select(external_wc_wcBlocksData_namespaceObject.CART_STORE_KEY).getCartData().items;
  });
  (0,external_wp_element_namespaceObject.useEffect)(function () {
    setTimeout(function () {
      if (cartItems.length > 0) {
        // Change product thumbnail.
        var cartHtml = document.querySelector('.wc-block-cart .wc-block-cart__main') || document.querySelector('.wc-block-cart .wc-block-components-order-summary__content');
        var cartElements = {
          itemsRow: '.wc-block-cart-items__row',
          productMetadata: '.wc-block-components-product-metadata',
          itemLink: '.yith-wccl-cart-container'
        };
        var elements = null;
        var elementHtml = null;
        if (cartHtml !== null) {
          elementHtml = cartHtml;
          elements = cartElements;
        }
        if (elements !== null && elementHtml !== null) {
          var itemsRow = elementHtml.querySelectorAll(elements.itemsRow);
          itemsRow.forEach(function (itemRow, indexRow) {
            var _cartItems$indexRow$e;
            var editLink = (_cartItems$indexRow$e = cartItems[indexRow].extensions) === null || _cartItems$indexRow$e === void 0 ? void 0 : _cartItems$indexRow$e.yith_wccl_wc_cart_item_manager.edit_link;
            var itemLink = itemRow.querySelector(elements.itemLink);
            if (editLink && !itemLink) {
              var productMetadataDiv = itemRow.querySelector(elements.productMetadata);
              productMetadataDiv.innerHTML += editLink;
            }
          });
        }
      }
    }, 500);
  }, [cartItems]);
};
(0,external_wp_plugins_namespaceObject.registerPlugin)('yith-wccl-edit-link', {
  render: addEditLink,
  scope: 'woocommerce-checkout'
});
/******/ })()
;
//# sourceMappingURL=index.js.map