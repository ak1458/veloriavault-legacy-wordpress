/**
 * Shop listing (ShopContent.tsx): category + search filtering on the client,
 * synced to ?category= and ?search= without a page reload, exactly like the
 * Next.js router.push(..., { scroll: false }).
 */
export function shop() {
  return {
    category: "",
    search: "",
    sort: "",
    products: [],

    init() {
      this.products = JSON.parse(this.$refs.data.textContent || "[]");
      this.readUrl();
      window.addEventListener("popstate", () => this.readUrl());
    },

    readUrl() {
      const params = new URLSearchParams(window.location.search);
      this.category = params.get("category") || "";
      this.search = params.get("search") || "";
      this.sort = params.get("sort") || "";
      if (this.$refs.searchInput) this.$refs.searchInput.value = this.search;
    },

    // CSS order for card i: 0 keeps the server order; newest/bestsellers re-rank.
    rank(i) {
      if (this.sort !== "newest" && this.sort !== "bestsellers") return 0;
      const key = this.sort === "newest" ? "date" : "sales";
      const value = (p) => Number((p && p[key]) || 0);
      const mine = value(this.products[i]);
      return this.products.filter((p, j) => value(p) > mine || (value(p) === mine && j < i)).length;
    },

    matches(product) {
      if (!product || !product.categories) return true;
      if (this.category && !product.categories.includes(this.category)) return false;
      if (this.search) {
        const q = this.search.toLowerCase();
        if (!product.name.toLowerCase().includes(q) && !product.categoryNames.some((c) => c.toLowerCase().includes(q))) return false;
      }
      return true;
    },

    get count() {
      return this.products.filter((p) => this.matches(p)).length;
    },

    update(updates) {
      const params = new URLSearchParams(window.location.search);
      ["category", "search", "sort"].forEach((key) => {
        if (key in updates) {
          if (updates[key]) params.set(key, updates[key]);
          else params.delete(key);
        }
      });
      const qs = params.toString();
      window.history.pushState({}, "", qs ? `${window.location.pathname}?${qs}` : window.location.pathname);
      this.readUrl();
    },

    setCategory(slug) {
      this.update({ category: slug || "" });
    },

    submitSearch() {
      this.update({ search: (this.$refs.searchInput.value || "").trim() });
    },
  };
}
