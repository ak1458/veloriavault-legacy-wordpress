/**
 * Storefront entry point (bundled to assets/dist/app.js by esbuild).
 */
import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse";

import cart from "./stores/cart.js";
import wishlist from "./stores/wishlist.js";
import chat from "./stores/chat.js";
import { inr } from "./lib/api.js";
import { header, announcement, newsletter, scrollTop } from "./components/site.js";
import { heroSlider, reviewsSlider, homeTabs } from "./components/home.js";
import { productDetails, reviewSection, reviewModal } from "./components/product.js";
import { shop } from "./components/shop.js";
import { checkout } from "./components/checkout.js";
import { account, returnForm, trackOrder, login, register, nativeOrderTools } from "./components/account.js";
import { contact } from "./components/contact.js";
import { chatWidget, chatCarousel } from "./components/chat.js";

Alpine.plugin(collapse);

Alpine.store("cart", cart);
Alpine.store("wishlist", wishlist);
Alpine.store("chat", chat);

Alpine.magic("inr", () => inr);

Alpine.data("header", header);
Alpine.data("announcement", announcement);
Alpine.data("newsletter", newsletter);
Alpine.data("heroSlider", heroSlider);
Alpine.data("reviewsSlider", reviewsSlider);
Alpine.data("homeTabs", homeTabs);
Alpine.data("productDetails", productDetails);
Alpine.data("reviewSection", reviewSection);
Alpine.data("reviewModal", reviewModal);
Alpine.data("shop", shop);
Alpine.data("checkout", checkout);
Alpine.data("account", account);
Alpine.data("nativeOrderTools", nativeOrderTools);
Alpine.data("returnForm", returnForm);
Alpine.data("trackOrder", trackOrder);
Alpine.data("login", login);
Alpine.data("register", register);
Alpine.data("contact", contact);
Alpine.data("chatWidget", chatWidget);
Alpine.data("chatCarousel", chatCarousel);

window.veloriaScrollTop = scrollTop;
window.Alpine = Alpine;

/** Product-card helpers used from server-rendered markup. */
window.veloriaCard = {
  add(item) {
    if (item.type === "variable") {
      window.location.assign(item.href);
      return;
    }
    Alpine.store("cart").addItem(item);
    Alpine.store("cart").openCart();
  },
};

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && Alpine.store("cart").isOpen) Alpine.store("cart").closeCart();
});

Alpine.start();

// Native WooCommerce forms and third-party widgets update the server cart too.
// Refresh the shared header count after those operations, without replacing
// WooCommerce's checkout or fragment handlers.
if (window.jQuery) {
  window.jQuery(document.body).on("added_to_cart removed_from_cart updated_cart_totals updated_checkout wc_fragments_refreshed", () => {
    Alpine.store("cart").load();
  });
  // The COD fee and prepaid bonus depend on the gateway, so re-total on switch.
  window.jQuery(document.body).on("change", "form.checkout input[name='payment_method']", () => {
    window.jQuery(document.body).trigger("update_checkout");
  });
}
