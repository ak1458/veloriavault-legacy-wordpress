/**
 * Checkout (src/app/checkout/page.tsx + OrderSummary.tsx + CouponSection.tsx).
 *
 * Same two-step form, validation messages and COD prompt as before, but the
 * order is placed through WooCommerce's native checkout endpoint
 * (?wc-ajax=checkout). WooCommerce creates the order BEFORE payment; the
 * official Razorpay plugin then collects payment on the order-pay page and
 * its webhook marks the order paid even if the shopper closes the tab.
 */
import { cfg, rest, nonces } from "../lib/api.js";

const RULES = {
  email: [(v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), "Please enter a valid email"],
  firstName: [(v) => v.trim().length >= 1, "First name is required"],
  lastName: [(v) => v.trim().length >= 1, "Last name is required"],
  address: [(v) => v.trim().length >= 5, "Please enter a complete address"],
  city: [(v) => v.trim().length >= 2, "City is required"],
  state: [(v) => v.trim().length >= 2, "State is required"],
  postalCode: [(v) => /^[1-9]\d{5}$/.test(v.replace(/\s/g, "")), "Please enter a valid 6-digit PIN code"],
  phone: [(v) => /^[6-9]\d{9}$/.test(normalizePhone(v)), "Please enter a valid 10-digit mobile number"],
};

function normalizePhone(value) {
  const digits = String(value || "").replace(/\D/g, "");
  if (digits.length === 12 && digits.startsWith("91")) return digits.slice(2);
  if (digits.length === 11 && digits.startsWith("0")) return digits.slice(1);
  return digits;
}

function textFromHtml(html) {
  const div = document.createElement("div");
  div.innerHTML = html || "";
  return (div.textContent || "").replace(/\s+/g, " ").trim();
}

export function checkout() {
  const legacy = (() => {
    try {
      return JSON.parse(localStorage.getItem("veloria-coupons") || "null");
    } catch {
      return null;
    }
  })();

  return {
    step: 1,
    form: { email: "", firstName: "", lastName: "", address: "", city: "", state: "", postalCode: "", phone: "" },
    errors: {},
    attempted: false,
    isPrepaid: legacy && legacy.state && legacy.state.isPrepaid === false ? false : true,
    calculation: null,
    appliedCodes: [],
    hasActiveCoupons: false,
    couponInput: "",
    couponError: null,
    applying: false,
    submitting: false,
    orderError: null,
    showCodModal: false,
    codConfirmed: false,

    init() {
      const prefill = this.$el.dataset.prefill ? JSON.parse(this.$el.dataset.prefill) : {};
      Object.keys(this.form).forEach((k) => {
        if (prefill[k]) this.form[k] = prefill[k];
      });
      this.refresh();
      window.addEventListener("veloria:cart-updated", () => this.refresh());
      this.$watch("form", () => {
        if (this.attempted) this.validate();
      });
    },

    get items() {
      return Alpine.store("cart").items;
    },

    get empty() {
      return Alpine.store("cart").loaded && this.items.length === 0;
    },

    get subtotal() {
      return this.calculation ? this.calculation.originalSubtotal : Alpine.store("cart").subtotal;
    },

    get totalSavings() {
      return this.calculation ? this.calculation.savingsBreakdown.reduce((s, x) => s + x.amount, 0) : 0;
    },

    get finalTotal() {
      return this.calculation ? this.calculation.finalTotal : 0;
    },

    paymentMethod() {
      return this.isPrepaid ? cfg.prepaidGateway : cfg.codGateway;
    },

    async refresh() {
      const { ok, data } = await rest("checkout/summary", { method: "POST", body: { paymentMethod: this.paymentMethod() } });
      if (ok) {
        this.calculation = data.calculation;
        this.appliedCodes = data.appliedCodes || [];
        this.hasActiveCoupons = Boolean(data.hasActiveCoupons);
      }
    },

    setPrepaid(value) {
      if (this.isPrepaid === value) return;
      this.isPrepaid = value;
      try {
        localStorage.setItem("veloria-coupons", JSON.stringify({ state: { isPrepaid: value }, version: 0 }));
      } catch {
        /* ignore */
      }
      this.refresh();
    },

    validate(fields = Object.keys(RULES)) {
      const errors = { ...this.errors };
      fields.forEach((field) => {
        const [check, message] = RULES[field];
        if (check(String(this.form[field] || ""))) delete errors[field];
        else errors[field] = message;
      });
      this.errors = errors;
      return fields.every((f) => !errors[f]);
    },

    next() {
      this.attempted = true;
      if (this.validate()) {
        this.step = 2;
        this.orderError = null;
      }
    },

    back() {
      this.step = 1;
      this.orderError = null;
    },

    async applyCoupon() {
      const code = this.couponInput.trim();
      if (!code || this.applying) return;
      this.applying = true;
      this.couponError = null;
      const { ok, data } = await rest("coupons/apply", { method: "POST", body: { code } });
      this.applying = false;
      if (ok) {
        this.couponInput = "";
        this.calculation = data.calculation;
        this.appliedCodes = data.appliedCodes;
      } else {
        this.couponError = data.error || "Coupon could not be applied.";
      }
    },

    async removeCoupon(code) {
      const { ok, data } = await rest("coupons/remove", { method: "POST", body: { code } });
      if (ok) {
        this.calculation = data.calculation;
        this.appliedCodes = data.appliedCodes;
      }
    },

    couponAmount(code) {
      if (!this.calculation) return null;
      const entry = this.calculation.appliedCoupons.find((c) => c.coupon.code.toUpperCase() === code);
      return entry ? entry.discountAmount : null;
    },

    submit() {
      if (this.step === 1) return this.next();
      if (!this.isPrepaid && !this.codConfirmed) {
        this.showCodModal = true;
        return;
      }
      return this.placeOrder();
    },

    switchToPrepaid() {
      this.setPrepaid(true);
      this.showCodModal = false;
    },

    continueCod() {
      this.codConfirmed = true;
      this.showCodModal = false;
      this.placeOrder();
    },

    async placeOrder() {
      this.attempted = true;
      if (!this.validate()) {
        this.step = 1;
        return;
      }
      this.submitting = true;
      this.orderError = null;

      const fresh = await nonces(true);
      const body = new URLSearchParams({
        billing_email: this.form.email.trim(),
        billing_first_name: this.form.firstName.trim(),
        billing_last_name: this.form.lastName.trim(),
        billing_address_1: this.form.address.trim(),
        billing_city: this.form.city.trim(),
        billing_state: this.form.state.trim(),
        billing_postcode: this.form.postalCode.replace(/\s/g, ""),
        billing_phone: normalizePhone(this.form.phone),
        billing_country: "IN",
        payment_method: this.paymentMethod(),
        "shipping_method[0]": "veloria_standard",
        "woocommerce-process-checkout-nonce": fresh.checkout || cfg.checkoutNonce,
        _wp_http_referer: "/checkout",
      });

      try {
        const res = await fetch(cfg.checkoutAjax, {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
          body: body.toString(),
        });
        const data = await res.json();
        if (data.result === "success" && data.redirect) {
          window.location.assign(data.redirect);
          return;
        }
        if (data.reload) {
          window.location.reload();
          return;
        }
        this.orderError = textFromHtml(data.messages) || "Failed to process checkout";
        this.refresh();
      } catch {
        this.orderError = "Something went wrong";
      }
      this.submitting = false;
      this.codConfirmed = false;
    },
  };
}
