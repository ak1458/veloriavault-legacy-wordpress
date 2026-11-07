/**
 * Account area and guest order tools:
 *   account()      src/app/account/page.tsx
 *   returnForm()   src/app/account/return/[orderId]/page.tsx
 *   trackOrder()   src/app/track-order/page.tsx
 *   login()        src/app/login/page.tsx
 *   register()     src/app/register/page.tsx
 */
import { cfg, rest, nonces, downloadInvoice } from "../lib/api.js";

async function latestOrder(orderId, orderNumber, email) {
  const { ok, data } = await rest("orders/track", { query: { orderNumber, email } });
  return ok && Number(data.order?.id) === Number(orderId) ? data.order : null;
}

const cancellationLabel = (order) => order.cancelMode === "review" ? "Request cancellation review" : "Cancel order";
const cancellationConfirmation = (order) => order.cancelMode === "review"
  ? `Request cancellation review for order #${order.number}? Our team must approve the request and confirm dispatch can be stopped before any refund. Your order will be placed on hold for review.`
  : `Cancel order #${order.number}? Cancellation must be confirmed before any online refund goes to the original payment method. Courier checks may need staff help. Bank timing varies; an unpaid COD order has no refund.`;
const cancellationMessage = (data) => data.pendingReview
  ? data.message || "Cancellation review requested. Our team must approve it before any refund."
  : data.refunded
    ? "Order cancelled. Your refund has been initiated to the original payment method. Your payment provider or bank determines when it appears."
    : "Order cancelled.";

export function nativeOrderTools(orderId, email, orderStatus, cancelMode = "automatic") {
  return {
    busy: false,
    message: "",
    canCancel: true,
    cancelMode,
    stateStale: false,
    get cancelLabel() { return cancellationLabel(this); },
    async invoice() {
      this.busy = true;
      try { await downloadInvoice(orderId); }
      catch (error) { this.message = error.message || "Invoice could not be downloaded."; }
      finally { this.busy = false; }
    },
    async cancel() {
      if (this.busy || this.stateStale || !this.canCancel) return;
      if (!window.confirm(cancellationConfirmation({ number: orderId, cancelMode: this.cancelMode }))) return;
      this.busy = true;
      const { ok, data } = await rest("orders/cancel", { method: "POST", body: { orderId } });
      this.message = ok ? cancellationMessage(data) : data.error || "Cancellation needs support review.";
      if (ok) {
        this.canCancel = false;
        window.location.reload();
        return;
      }
      const fresh = await latestOrder(orderId, String(orderId), email);
      if (fresh) {
        this.canCancel = fresh.canCancel;
        this.cancelMode = fresh.cancelMode;
        if (fresh.refundProgress || fresh.cancelReviewState || fresh.status !== orderStatus) window.location.reload();
      } else {
        this.stateStale = true;
        this.message += " Refresh this page to see the latest order details before trying again.";
      }
      this.busy = false;
    },
  };
}

const ORDER_STATUS_COLORS = {
  pending: "bg-amber-100 text-amber-700",
  processing: "bg-blue-100 text-blue-700",
  completed: "bg-green-100 text-green-700",
  cancelled: "bg-red-100 text-red-700",
};

const TRACK_STATUS_COLORS = {
  pending: "bg-amber-100 text-amber-700",
  "on-hold": "bg-amber-100 text-amber-700",
  processing: "bg-blue-100 text-blue-700",
  shipped: "bg-purple-100 text-purple-700",
  "out for delivery": "bg-orange-100 text-orange-700",
  completed: "bg-green-100 text-green-700",
  delivered: "bg-green-100 text-green-700",
  cancelled: "bg-red-100 text-red-700",
  refunded: "bg-gray-100 text-gray-700",
};

export const dateLong = (iso) => new Date(iso).toLocaleDateString("en-IN", { year: "numeric", month: "long", day: "numeric" });
export const dateShort = (iso) => new Date(iso).toLocaleDateString("en-IN");

export function account() {
  return {
    loading: true,
    customer: null,
    sessionEmail: null,
    orders: [],
    error: null,
    tab: "orders",
    trackingOrder: null,
    liveTracking: null,
    searching: false,
    cancellingId: null,
    invoiceId: null,
    ordersLoaded: false,
    ordersStateStale: false,

    async init() {
      const tab = window.location.hash.replace("#", "");
      if (["orders", "profile", "track", "returns", "support"].includes(tab)) this.tab = tab;
      await this.fetchProfile();
    },

    async fetchProfile() {
      const me = await rest("account/me");
      if (!me.ok) {
        window.location.assign(cfg.urls.login);
        return;
      }
      if (me.data.sessionType === "customer") this.customer = me.data.customer;
      else this.sessionEmail = me.data.email;
      await this.fetchOrders();
      this.loading = false;
    },

    async fetchOrders() {
      const response = await rest("account/orders");
      if (!response.ok) {
        this.ordersStateStale = true;
        this.error = "Could not refresh your orders. Refresh the order list before trying another action.";
        return false;
      }
      this.orders = response.data.orders || [];
      this.ordersLoaded = true;
      this.ordersStateStale = false;
      this.error = null;
      if (this.trackingOrder) this.trackingOrder = this.orders.find((order) => order.id === this.trackingOrder.id) || null;
      return true;
    },

    get returnOrders() {
      return this.orders.filter((order) => order.returnRequested || order.returnState || order.cancelReviewState || order.refundProgress || Number(order.amountRefunded) > 0);
    },

    returnStatus(order) {
      if (Number(order.amountRefunded) > 0) return "Refund recorded";
      if (order.returnState === "approved") return "Return approved after inspection";
      if (order.returnState === "declined") return "Return declined";
      if (order.returnRequested) return "Return awaiting inspection";
      if (order.cancelReviewState === "requested") return "Cancellation awaiting review";
      if (order.cancelReviewState === "declined") return "Cancellation request declined";
      if (order.status === "cancelled") return "Order cancelled";
      if (order.cancelReviewState === "approved") return "Cancellation approved; checking refund progress";
      return "Cancellation refund in progress";
    },

    get displayName() {
      return (this.customer && this.customer.displayName) || this.sessionEmail || "Guest";
    },
    get displayEmail() {
      return (this.customer && this.customer.email) || this.sessionEmail || "";
    },
    get initial() {
      return ((this.customer && this.customer.firstName) || this.sessionEmail || "G").charAt(0).toUpperCase();
    },
    get greetingName() {
      return (this.customer && this.customer.firstName) || this.sessionEmail || "there";
    },

    statusColor(status) {
      return ORDER_STATUS_COLORS[status] || "bg-gray-100 text-gray-700";
    },

    statusStep(status) {
      const s = String(status).toLowerCase();
      if (s === "pending" || s === "processing") return 1;
      if (s === "shipped") return 2;
      if (s === "out for delivery") return 3;
      if (s === "completed" || s === "delivered") return 4;
      return 1;
    },

    async invoice(order) {
      this.invoiceId = order.id;
      this.error = null;
      try {
        await downloadInvoice(order.id);
      } catch (e) {
        this.error = e.message || "Could not download your invoice.";
      } finally {
        this.invoiceId = null;
      }
    },

    returnUrl(order) {
      return `${cfg.urls.account}/return/${order.id}`;
    },

    cancellationLabel,

    openTrackTab() {
      if (!this.trackingOrder && this.orders.length > 0) this.track(this.orders[0]);
      else this.tab = "track";
    },

    async track(order) {
      this.trackingOrder = order;
      this.tab = "track";
      this.searching = true;
      this.liveTracking = null;
      const started = Date.now();
      const { ok, data } = await rest("orders/track-live", { query: { orderId: order.id } });
      // Keep the truck animation on screen for its full 2.5 s, as before.
      const wait = Math.max(0, 2500 - (Date.now() - started));
      setTimeout(() => {
        this.liveTracking = ok ? data : null;
        this.searching = false;
      }, wait);
    },

    selectTracked(id) {
      const order = this.orders.find((o) => o.id === Number(id));
      if (order) this.track(order);
    },

    async cancel(order) {
      if (this.cancellingId || this.ordersStateStale || !order.canCancel) return;
      if (!window.confirm(cancellationConfirmation(order))) return;
      this.cancellingId = order.id;
      const { ok, data } = await rest("orders/cancel", { method: "POST", body: { orderId: order.id } });
      await this.fetchOrders();
      this.cancellingId = null;
      if (!ok) {
        window.alert(data.error || "Could not cancel this order.");
        return;
      }
      window.alert(cancellationMessage(data));
    },

    async logout() {
      await rest("auth/logout", { method: "POST" });
      await nonces(true);
      window.location.assign(cfg.home);
    },

    dateLong,
    dateShort,
    inr: (v) => `₹${parseFloat(v || 0).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
  };
}

export function returnForm(orderId) {
  return {
    reason: "",
    loading: false,
    done: false,
    error: null,
    async submit() {
      this.loading = true;
      this.error = null;
      const { ok, data } = await rest("orders/return", { method: "POST", body: { orderId, reason: this.reason } });
      this.loading = false;
      if (ok) this.done = true;
      else this.error = data.error || "Could not submit your return request.";
    },
  };
}

export function trackOrder() {
  return {
    orderId: "",
    email: "",
    order: null,
    loading: false,
    error: null,
    actionLoading: false,
    actionMsg: null,
    orderStateStale: false,
    showReturn: false,
    returnReason: "",
    otpStep: "email",
    code: "",
    verifying: false,
    info: null,
    steps: ["Order Placed", "Processing", "Shipped", "Out for Delivery", "Delivered"],

    async track() {
      this.loading = true;
      this.error = null;
      this.order = null;
      this.orderStateStale = false;
      const { ok, data } = await rest("orders/track", { query: { orderNumber: this.orderId, email: this.email } });
      this.loading = false;
      if (ok) this.order = data.order;
      else this.error = data.error || "Order not found";
    },

    async refreshOrder() {
      if (!this.order) return false;
      const previous = this.order;
      const fresh = await latestOrder(previous.id, previous.number, this.email || previous.billing?.email);
      if (this.order?.id !== previous.id) return false;
      this.orderStateStale = !fresh;
      if (fresh) this.order = fresh;
      return !!fresh;
    },

    async requestCode() {
      if (!this.email || !this.orderId) {
        this.error = "Enter your order number and order email first.";
        return;
      }
      this.verifying = true;
      this.error = null;
      this.info = null;
      const { ok, data } = await rest("auth/otp/request", { method: "POST", body: { email: this.email } });
      this.verifying = false;
      if (ok) {
        this.otpStep = "code";
        this.info = "If a guest order exists for this email, a 6-digit code is on its way. Check your inbox.";
      } else this.error = data.error || "Could not send code.";
    },

    async verifyCode() {
      this.verifying = true;
      this.error = null;
      const { ok, data } = await rest("auth/otp/verify", { method: "POST", body: { code: this.code } });
      this.verifying = false;
      if (!ok) {
        this.error = data.error || "Invalid or expired code.";
        return;
      }
      await nonces(true);
      this.info = "Email verified.";
      await this.track();
    },

    differentEmail() {
      this.otpStep = "email";
      this.code = "";
      this.info = null;
      this.error = null;
    },

    reset() {
      this.order = null;
      this.error = null;
      this.orderStateStale = false;
      this.showReturn = false;
      this.actionMsg = null;
    },

    statusColor(status) {
      return TRACK_STATUS_COLORS[String(status).toLowerCase()] || "bg-gray-100 text-gray-700";
    },

    cancellationLabel,

    statusStep(status) {
      const s = String(status).toLowerCase();
      if (s === "pending" || s === "on-hold") return 1;
      if (s === "processing") return 2;
      if (s === "shipped") return 3;
      if (s === "out for delivery") return 4;
      if (s === "completed" || s === "delivered") return 5;
      return 1;
    },

    async invoice() {
      this.actionLoading = true;
      this.actionMsg = null;
      try {
        await downloadInvoice(this.order.id);
      } catch (e) {
        this.actionMsg = { type: "err", text: e.message || "Could not download your invoice." };
      } finally {
        this.actionLoading = false;
      }
    },

    async cancel() {
      if (this.actionLoading || this.orderStateStale || !this.order?.canCancel) return;
      if (!window.confirm(cancellationConfirmation(this.order))) return;
      this.actionLoading = true;
      this.actionMsg = null;
      const { ok, data } = await rest("orders/cancel", { method: "POST", body: { orderId: this.order.id } });
      const refreshed = await this.refreshOrder();
      this.actionLoading = false;
      if (ok) {
        this.actionMsg = { type: data.pendingReview ? "info" : "ok", text: cancellationMessage(data) };
      } else if (refreshed && this.order.refundProgress) {
        this.actionMsg = { type: "info", text: this.order.refundProgress };
      } else {
        this.actionMsg = { type: "err", text: data.error || "Could not cancel this order." };
      }
    },

    async submitReturn() {
      if (this.actionLoading || this.orderStateStale) return;
      this.actionLoading = true;
      this.actionMsg = null;
      const { ok, data } = await rest("orders/return", { method: "POST", body: { orderId: this.order.id, reason: this.returnReason } });
      await this.refreshOrder();
      this.actionLoading = false;
      if (ok) {
        this.actionMsg = { type: "ok", text: "Return request submitted. Our team will email you the next steps." };
        this.showReturn = false;
        this.returnReason = "";
      } else {
        this.actionMsg = { type: "err", text: data.error || "Could not submit your return request." };
      }
    },

    capitalize: (s) => s.charAt(0).toUpperCase() + s.slice(1),
    dateShort,
    inr: (v) => `₹${parseFloat(v || 0).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
  };
}

export function login() {
  return {
    mode: "password",
    otpStep: "email",
    email: "",
    password: "",
    code: "",
    showPassword: false,
    loading: false,
    error: null,
    info: null,

    setMode(mode) {
      this.mode = mode;
      this.otpStep = "email";
      this.error = null;
      this.info = null;
    },

    async afterLogin() {
      await nonces(true);
      window.location.assign(cfg.urls.account);
    },

    async submitPassword() {
      this.loading = true;
      this.error = null;
      const { ok, data } = await rest("auth/login", { method: "POST", body: { email: this.email, password: this.password } });
      if (ok) return this.afterLogin();
      this.loading = false;
      this.error = data.error || "Invalid email or password";
    },

    async requestCode() {
      this.loading = true;
      this.error = null;
      const { ok, data } = await rest("auth/otp/request", { method: "POST", body: { email: this.email } });
      this.loading = false;
      if (ok) {
        this.otpStep = "code";
        this.info = `If an order exists for ${this.email}, a 6-digit code is on its way. Check your inbox.`;
      } else {
        this.error = data.error || "Could not send code";
      }
    },

    async verifyCode() {
      this.loading = true;
      this.error = null;
      const { ok, data } = await rest("auth/otp/verify", { method: "POST", body: { code: this.code } });
      if (ok) return this.afterLogin();
      this.loading = false;
      this.error = data.error || "Invalid or expired code";
    },

    differentEmail() {
      this.otpStep = "email";
      this.code = "";
      this.error = null;
      this.info = null;
    },
  };
}

export function register() {
  return {
    form: { firstName: "", lastName: "", email: "", password: "" },
    showPassword: false,
    loading: false,
    error: null,
    async submit() {
      this.loading = true;
      this.error = null;
      const { ok, data } = await rest("auth/register", { method: "POST", body: this.form });
      if (ok) {
        await nonces(true);
        window.location.assign(cfg.urls.account);
        return;
      }
      this.loading = false;
      this.error = data.error || "Failed to create account";
    },
  };
}
