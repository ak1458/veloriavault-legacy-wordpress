/**
 * Wishlist store. Reads and writes the exact localStorage format of the
 * headless Zustand store ("veloria-wishlist": { state: { items }, version: 0 }),
 * so wishlists saved before the migration are still there afterwards.
 */
import { storage } from "../lib/api.js";

const KEY = "veloria-wishlist";

export default {
  items: [],

  init() {
    const saved = storage.get(KEY);
    this.items = saved && saved.state && Array.isArray(saved.state.items) ? saved.state.items : [];
    window.addEventListener("storage", (e) => {
      if (e.key === KEY) this.init();
    });
  },

  persist() {
    storage.set(KEY, { state: { items: this.items }, version: 0 });
  },

  has(id) {
    return this.items.some((i) => i.id === id);
  },

  add(item) {
    if (!this.has(item.id)) {
      this.items.push(item);
      this.persist();
    }
  },

  remove(id) {
    this.items = this.items.filter((i) => i.id !== id);
    this.persist();
  },

  toggle(item) {
    if (this.has(item.id)) this.remove(item.id);
    else this.add(item);
  },
};
