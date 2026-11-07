/**
 * Site shell: header (PremiumHeader.tsx), announcement bar, footer newsletter.
 */
import { rest, lockScroll } from "../lib/api.js";

export function header() {
  return {
    mobileOpen: false,
    hovered: null,
    expanded: null,
    init() {
      this.$watch("mobileOpen", (open) => {
        lockScroll(open);
        if (open) {
          // Back button closes the menu instead of leaving the page.
          window.history.pushState({ mobileMenu: true }, "");
        }
      });
      window.addEventListener("popstate", () => {
        if (this.mobileOpen) this.mobileOpen = false;
      });
    },
    toggle() {
      if (this.mobileOpen) this.dismiss();
      else this.mobileOpen = true;
    },
    // Menu links: the page navigates away, so the history entry does not matter.
    close() {
      this.mobileOpen = false;
    },
    // X, backdrop, Escape: drop the entry pushed on open so the next Back leaves the page.
    dismiss() {
      if (!this.mobileOpen) return;
      if (window.history.state && window.history.state.mobileMenu) window.history.back();
      else this.mobileOpen = false;
    },
  };
}

export function announcement(count) {
  return {
    index: 0,
    init() {
      if (count > 1) setInterval(() => (this.index = (this.index + 1) % count), 3500);
    },
  };
}

export function newsletter() {
  return {
    email: "",
    loading: false,
    message: null,
    async submit() {
      if (!this.email.trim()) {
        this.message = { type: "error", text: "Please enter your email address" };
        return;
      }
      this.loading = true;
      this.message = null;
      const { ok, data } = await rest("newsletter", { method: "POST", body: { email: this.email } });
      this.loading = false;
      if (ok) {
        this.message = { type: "success", text: data.message };
        this.email = "";
      } else {
        this.message = { type: "error", text: data.error || "Failed to subscribe" };
      }
    },
  };
}

export function scrollTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
}
