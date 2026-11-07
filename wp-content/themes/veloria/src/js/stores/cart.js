/**
 * Cart store, backed by the WooCommerce Store API (the server cart is the
 * single source of truth, so stock and prices are always live).
 *
 * Same item shape and actions as the Zustand store in src/store/cart.ts:
 *   { id, name, slug, href, price, image, category, quantity }
 *   addItem(item, quantity) · removeItem(id) · updateQuantity(id, quantity)
 *   openCart() · closeCart() · totalItems · subtotal
 *
 * Updates are optimistic (instant like before) and then reconciled with the
 * server response.
 */
import { storeApi, storage, lockScroll } from "../lib/api.js";

const CACHE_KEY = "veloria-cart-cache";
const LEGACY_KEY = "veloria-cart"; // Zustand store on the headless site.
const MIGRATED_KEY = "veloria-cart-migrated";

/** Store API messages can carry HTML entities (&quot; etc.); x-text would show them raw. */
function plainText(message) {
  if (!message) return "";
  return new DOMParser().parseFromString(String(message), "text/html").body.textContent || "";
}

function mapItem(item) {
  const ext = (item.extensions && item.extensions.veloria) || {};
  const minor = item.prices ? item.prices.currency_minor_unit : 2;
  return {
    key: item.key,
    id: item.id,
    name: ext.displayName || item.name,
    slug: "",
    href: ext.href || item.permalink,
    price: Number(item.prices ? item.prices.price : 0) / 10 ** minor,
    image: ext.image || (item.images && item.images[0] ? item.images[0].thumbnail || item.images[0].src : ""),
    category: ext.category || "",
    quantity: item.quantity,
  };
}

export default {
  items: [],
  isOpen: false,
  loaded: false,
  error: null,
  calculation: null,
  migrating: false,

  init() {
    const cached = storage.get(CACHE_KEY);
    if (cached && Array.isArray(cached.items)) this.items = cached.items;
    this.load().then((loaded) => { if (loaded) this.migrateLegacy(); });
  },

  get totalItems() {
    return this.items.reduce((sum, i) => sum + i.quantity, 0);
  },

  get subtotal() {
    return this.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
  },

  setFromServer(cart) {
    this.items = (cart.items || []).map(mapItem);
    this.calculation = cart.extensions && cart.extensions.veloria ? cart.extensions.veloria.calculation : null;
    this.loaded = true;
    storage.set(CACHE_KEY, { items: this.items });
    window.dispatchEvent(new CustomEvent("veloria:cart-updated", { detail: { items: this.items } }));
  },

  async load() {
    try {
      this.setFromServer(await storeApi("cart"));
      return true;
    } catch {
      this.loaded = true;
      return false;
    }
  },

  /** Import a cart left in localStorage by the headless storefront (once). */
  async migrateLegacy() {
    if (this.migrating) return;
    this.migrating = true;
    const migrate = async () => {
      const legacy = storage.get(LEGACY_KEY);
      const items = legacy && legacy.state && Array.isArray(legacy.state.items) ? legacy.state.items : [];
      if (!items.length) return;
      // Re-read inside the cross-tab lock. A failed load cannot establish which
      // earlier additions reached the server, so leave the original intact.
      if (!await this.load()) return;
      let complete = true;
      for (const item of items) {
        const id = Number(item.id);
        const quantity = Math.max(1, Number(item.quantity) || 1);
        if (!Number.isInteger(id) || id <= 0 || !Number.isInteger(quantity)) {
          complete = false;
          continue;
        }
        const already = this.items.find((i) => Number(i.id) === id);
        if (already && already.quantity >= quantity) continue;
        try {
          const path = already ? "cart/update-item" : "cart/add-item";
          const body = already ? { key: already.key, quantity } : { id, quantity };
          this.setFromServer(await storeApi(path, { method: "POST", body }));
        } catch {
          // The response may have been lost after the server saved the item.
          // Reconcile before another attempt instead of blindly adding twice.
          if (!await this.load()) {
            complete = false;
            break;
          }
        }
        if (!this.items.some((i) => Number(i.id) === id && i.quantity >= quantity)) complete = false;
      }
      if (complete) {
        storage.set(MIGRATED_KEY, true);
        storage.remove(LEGACY_KEY);
        this.error = null;
      } else {
        this.error = "Some items from your previous cart could not be restored. Your saved cart is kept so you can retry.";
      }
    };
    try {
      if (navigator.locks) await navigator.locks.request("veloria-legacy-cart", migrate);
      else await migrate();
    } finally {
      this.migrating = false;
    }
  },

  // Mutations run one at a time, so responses apply in tap order and an
  // optimistic line (key: null) has its server key before it is changed.
  queue: Promise.resolve(),

  enqueue(task) {
    const run = this.queue.then(task, task);
    this.queue = run.catch(() => {});
    return run;
  },

  /** Server cart-item key for a product id, reloading once if the line is still optimistic. */
  async keyFor(id) {
    let item = this.items.find((i) => i.id === id);
    if (item && !item.key) {
      await this.load();
      item = this.items.find((i) => i.id === id);
    }
    return item && item.key ? item.key : null;
  },

  addItem(item, quantity = 1) {
    this.error = null;
    const existing = this.items.find((i) => i.id === item.id);
    if (existing) {
      existing.quantity += quantity;
    } else {
      this.items.push({ ...item, key: null, quantity });
    }
    return this.enqueue(async () => {
      try {
        this.setFromServer(await storeApi("cart/add-item", { method: "POST", body: { id: item.id, quantity } }));
      } catch (e) {
        this.error = plainText(e.message) || "This item could not be added to your cart.";
        // Drop the optimistic line if the server cart cannot even be reloaded.
        if (!(await this.load())) this.items = this.items.filter((i) => i.key || i.id !== item.id);
      }
    });
  },

  updateQuantity(id, quantity) {
    if (quantity <= 0) return this.removeItem(id);
    const item = this.items.find((i) => i.id === id);
    if (!item) return;
    this.error = null;
    item.quantity = quantity;
    return this.enqueue(async () => {
      const key = await this.keyFor(id);
      if (!key) return;
      try {
        this.setFromServer(await storeApi("cart/update-item", { method: "POST", body: { key, quantity } }));
      } catch (e) {
        this.error = plainText(e.message) || "Could not update quantity.";
        await this.load();
      }
    });
  },

  removeItem(id) {
    const item = this.items.find((i) => i.id === id);
    if (!item) return;
    this.error = null;
    this.items = this.items.filter((i) => i.id !== id);
    return this.enqueue(async () => {
      let key = item.key;
      if (!key) {
        await this.load();
        const fresh = this.items.find((i) => i.id === id);
        key = fresh && fresh.key;
        if (!key) return;
        this.items = this.items.filter((i) => i.id !== id);
      }
      try {
        this.setFromServer(await storeApi("cart/remove-item", { method: "POST", body: { key } }));
      } catch {
        await this.load();
      }
    });
  },

  clearLocal() {
    this.items = [];
    storage.set(CACHE_KEY, { items: [] });
  },

  openCart() {
    this.isOpen = true;
    lockScroll(true);
  },

  closeCart() {
    this.isOpen = false;
    lockScroll(false);
  },
};
