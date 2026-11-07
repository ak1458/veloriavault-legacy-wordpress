/**
 * Network helpers shared by every storefront module.
 *
 * Nonces are fetched from admin-ajax (never cached) instead of being baked
 * into the HTML, so full-page caching can't serve stale nonces.
 */
export const cfg = window.veloriaConfig || {};

let noncePromise = null;
let nonceFetchedAt = 0;
let noncePending = false;
let storeNonce = null;
const NONCE_CACHE_MS = 5 * 60 * 1000;
const NONCE_ERRORS = new Set(["rest_cookie_invalid_nonce", "woocommerce_rest_invalid_nonce", "woocommerce_rest_missing_nonce"]);

export function nonces(force = false) {
  if (noncePending) return noncePromise;
  if (!noncePromise || force || Date.now() - nonceFetchedAt >= NONCE_CACHE_MS) {
    noncePending = true;
    noncePromise = fetch(`${cfg.ajaxUrl}?action=veloria_nonces`, { credentials: "same-origin", cache: "no-store" })
      .then(async (r) => {
        if (!r.ok) throw new Error("Could not refresh session");
        const data = await r.json();
        if (!data.rest || !data.store) throw new Error("Invalid session response");
        nonceFetchedAt = Date.now();
        storeNonce = data.store;
        return data;
      })
      .catch(() => {
        // A temporary outage must not poison every request for this page's lifetime.
        noncePromise = null;
        return {};
      })
      .finally(() => { noncePending = false; });
  }
  return noncePromise;
}

function buildUrl(base, path, query) {
  const url = new URL(base + path.replace(/^\//, ""), window.location.origin);
  if (query) {
    Object.entries(query).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== "") url.searchParams.set(k, String(v));
    });
  }
  return url.toString();
}

async function restRequest(path, { method = "GET", body, query } = {}, retried = false) {
  const n = await nonces();
  if (!n.rest) throw new Error("Could not refresh session");
  const headers = { "X-Veloria-Request": "1" };
  if (n.rest) headers["X-WP-Nonce"] = n.rest;
  if (body !== undefined) headers["Content-Type"] = "application/json";
  const res = await fetch(buildUrl(cfg.restUrl, path, query), {
      method,
      credentials: "same-origin",
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
  });
  if (!retried && (res.status === 401 || res.status === 403)) {
    const failure = await res.clone().json().catch(() => ({}));
    // Retry only explicit pre-handler nonce rejections. Never replay a timeout,
    // payment failure or arbitrary 403 that might already have changed state.
    if (NONCE_ERRORS.has(failure.code)) {
      await nonces(true);
      return restRequest(path, { method, body, query }, true);
    }
  }
  return res;
}

/** Call /wp-json/veloria/v1/<path>. Resolves to { ok, status, data }. */
export async function rest(path, options = {}) {
  try {
    const res = await restRequest(path, options);
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
  } catch {
    return { ok: false, status: 0, data: { error: "Something went wrong. Please try again." } };
  }
}

/** Download a private invoice using the same authenticated REST request. */
export async function downloadInvoice(orderId) {
  const res = await restRequest("orders/invoice", { query: { orderId } });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.error || data.message || "Could not download your invoice.");
  }
  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `veloria-invoice-${String(orderId).replace(/[^0-9]/g, "")}.pdf`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

/** Call the WooCommerce Store API (/wp-json/wc/store/v1/<path>). */
export async function storeApi(path, { method = "GET", body } = {}, retried = false) {
  const n = await nonces();
  if (!n.store) throw new Error("Could not refresh session");
  if (!storeNonce) storeNonce = n.store || "";
  const headers = { Nonce: storeNonce };
  if (body !== undefined) headers["Content-Type"] = "application/json";
  const res = await fetch(buildUrl(cfg.storeUrl, path), {
    method,
    credentials: "same-origin",
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });
  const fresh = res.headers.get("Nonce");
  if (fresh) storeNonce = fresh;
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (!retried && (res.status === 401 || res.status === 403) && NONCE_ERRORS.has(data.code)) {
      await nonces(true);
      return storeApi(path, { method, body }, true);
    }
    const err = new Error(data?.message || "Request failed");
    err.status = res.status;
    err.data = data;
    throw err;
  }
  return data;
}

/** `n.toLocaleString("en-IN")`, prefixed with ₹. */
export function inr(value, options) {
  return `₹${Number(value || 0).toLocaleString("en-IN", options)}`;
}

/** JSON in localStorage, never throwing (private mode etc.). */
export const storage = {
  get(key, fallback = null) {
    try {
      const raw = window.localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch {
      return fallback;
    }
  },
  set(key, value) {
    try {
      window.localStorage.setItem(key, JSON.stringify(value));
    } catch {
      /* storage unavailable */
    }
  },
  remove(key) {
    try {
      window.localStorage.removeItem(key);
    } catch {
      /* storage unavailable */
    }
  },
};

/** Lock/unlock page scroll (drawers, menus, modals). */
export function lockScroll(locked) {
  document.body.style.overflow = locked ? "hidden" : "";
}
