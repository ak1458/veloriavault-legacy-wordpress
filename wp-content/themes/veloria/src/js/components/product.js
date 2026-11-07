/**
 * Product page: ProductDetails.tsx, ProductOffers.tsx, ProductAccordion.tsx,
 * ProductReviewSection.tsx and WriteReviewModal.tsx.
 */
import { cfg, lockScroll } from "../lib/api.js";

const VISIBLE_THUMBS = 3;

export function productDetails(config) {
  const { product, variations, fallbackImage, defaultVariationId } = config;

  return {
    manualId: null, // number | "cleared" | null
    urlId: null,
    unavailableColor: false,
    selectedImage: 0,
    quantity: 1,
    thumbsIndex: 0,
    zoomOpen: false,
    copied: false,
    variations,
    product,

    init() {
      const param = new URLSearchParams(window.location.search).get("attribute_pa_color");
      if (param) {
        const match = variations.find((v) => v.queryValue === param);
        this.urlId = match ? match.id : null;
        // Say so instead of silently showing another colour.
        this.unavailableColor = !match;
      }
      this.$watch("zoomOpen", (open) => lockScroll(open));
    },

    get selectedId() {
      if (this.manualId === "cleared") return null;
      return this.manualId ?? this.urlId ?? defaultVariationId;
    },

    get selected() {
      return variations.find((v) => v.id === this.selectedId) || null;
    },

    get display() {
      return this.selected || product;
    },

    get gallery() {
      const v = this.selected;
      if (!v) return product.images;
      if (v.images && v.images.length) return v.images;
      return v.image ? [v.image] : product.images;
    },

    get activeIndex() {
      return this.gallery.length ? Math.min(this.selectedImage, this.gallery.length - 1) : 0;
    },

    get currentImage() {
      return this.gallery[this.activeIndex] || this.gallery[0] || null;
    },

    get price() {
      return Number(this.display.price || this.display.regular_price || 0);
    },

    get regularPrice() {
      return Number(this.display.regular_price || 0);
    },

    get showRegular() {
      return this.display.on_sale && this.regularPrice > 0 && this.regularPrice > this.price;
    },

    get needsVariation() {
      return variations.length > 0 && !this.selected;
    },

    get inStock() {
      return this.display.stock_status !== "outofstock";
    },

    get currentColor() {
      return this.selected ? this.selected.color : "";
    },

    // Store API add_to_cart.maximum (stock or purchase limit); 99 when unlimited.
    get maxQty() {
      return Number(this.display.max_qty) > 0 ? Number(this.display.max_qty) : 99;
    },

    incrementQty() {
      this.quantity = Math.min(this.maxQty, this.quantity + 1);
    },

    get displayLink() {
      return this.selected ? this.selected.href : product.href;
    },

    get wishlisted() {
      return Alpine.store("wishlist").has(this.display.id);
    },

    get maxThumbIndex() {
      return Math.max(0, this.gallery.length - VISIBLE_THUMBS);
    },

    get resolvedThumbsIndex() {
      const active = this.activeIndex;
      let idx = this.thumbsIndex;
      if (active < idx) idx = active;
      else if (active >= idx + VISIBLE_THUMBS) idx = active - VISIBLE_THUMBS + 1;
      return Math.min(this.maxThumbIndex, Math.max(0, idx));
    },

    get visibleThumbs() {
      const start = this.resolvedThumbsIndex;
      return this.gallery.slice(start, start + VISIBLE_THUMBS).map((image, i) => ({ image, index: start + i }));
    },

    formatPrice(value) {
      return value.toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    scrollMobileTo(index, behavior = "smooth") {
      const el = this.$refs.mobileGallery;
      if (el) el.scrollTo({ left: el.clientWidth * index, behavior });
    },

    selectImage(index, syncMobile = false) {
      const bounded = Math.max(0, Math.min(index, this.gallery.length - 1));
      this.selectedImage = bounded;
      if (syncMobile) this.scrollMobileTo(bounded);
    },

    prevImage(syncMobile = false) {
      if (!this.gallery.length) return;
      this.selectImage(this.activeIndex === 0 ? this.gallery.length - 1 : this.activeIndex - 1, syncMobile);
    },

    nextImage(syncMobile = false) {
      if (!this.gallery.length) return;
      this.selectImage(this.activeIndex === this.gallery.length - 1 ? 0 : this.activeIndex + 1, syncMobile);
    },

    onMobileScroll() {
      const el = this.$refs.mobileGallery;
      if (!el || el.clientWidth === 0) return;
      const next = Math.round(el.scrollLeft / el.clientWidth);
      if (next !== this.activeIndex) this.selectedImage = next;
    },

    resetGallery() {
      this.selectedImage = 0;
      this.thumbsIndex = 0;
      this.scrollMobileTo(0, "auto");
    },

    selectVariation(option) {
      this.manualId = option.id;
      this.unavailableColor = false;
      this.quantity = Math.min(this.quantity, this.maxQty);
      this.syncUrl(option.queryValue);
      this.resetGallery();
    },

    clearVariation() {
      this.manualId = "cleared";
      this.syncUrl(null);
      this.resetGallery();
    },

    // Keep ?attribute_pa_color in step so refresh, Back and shared links show this colour.
    syncUrl(value) {
      try {
        const url = new URL(window.location.href);
        if (value) url.searchParams.set("attribute_pa_color", value);
        else url.searchParams.delete("attribute_pa_color");
        history.replaceState(history.state, "", url);
      } catch {
        /* ignore */
      }
    },

    item() {
      return {
        id: this.display.id,
        name: this.display.name,
        slug: this.display.slug,
        href: this.displayLink,
        price: this.price,
        image: (this.currentImage && this.currentImage.src) || fallbackImage,
        category: product.category,
      };
    },

    addToCart() {
      Alpine.store("cart").addItem(this.item(), this.quantity);
      Alpine.store("cart").openCart();
    },

    toggleWishlist() {
      Alpine.store("wishlist").toggle({ ...this.item(), originalPrice: this.showRegular ? this.regularPrice : undefined });
    },

    async copyLink() {
      try {
        await navigator.clipboard.writeText(window.location.href);
        this.copied = true;
        setTimeout(() => (this.copied = false), 2000);
      } catch {
        this.copied = false;
      }
    },
  };
}

export function reviewSection() {
  return {
    showAll: false,
    modalOpen: false,
    lightbox: null,
    openModal() {
      this.modalOpen = true;
    },
  };
}

/**
 * Review form. Talks directly to "Customer Reviews for WooCommerce"
 * (admin-ajax cr_upload_media / cr_submit_review) like the headless API
 * route did, falling back to the native WordPress comment form.
 */
export function reviewModal(productId) {
  return {
    rating: 0,
    hover: 0,
    name: "",
    email: "",
    review: "",
    website: "",
    images: [],
    submitting: false,
    submitted: false,
    error: null,

    get displayRating() {
      return this.hover || this.rating;
    },

    async upload(files) {
      if (!files) return;
      const remaining = 5 - this.images.length;
      for (const file of Array.from(files).slice(0, remaining)) {
        if (file.size > 5 * 1024 * 1024 || !/^image\/(jpeg|png|gif|webp)$/.test(file.type)) {
          this.images.push({ preview: URL.createObjectURL(file), token: null, uploading: false, error: "Upload failed" });
          continue;
        }
        const entry = { preview: URL.createObjectURL(file), token: null, uploading: true, error: null };
        this.images.push(entry);
        const form = new FormData();
        form.append("action", "cr_upload_media");
        form.append("cr_item", String(productId));
        form.append("cr_file", file);
        try {
          const res = await fetch(cfg.ajaxUrl, { method: "POST", body: form, credentials: "same-origin" });
          const data = await res.json();
          const target = this.images.find((i) => i.preview === entry.preview);
          if (data && data.code === 200 && data.attachment && data.attachment.id) {
            target.token = { id: data.attachment.id, key: data.attachment.key };
          } else {
            target.error = "Upload failed";
          }
          target.uploading = false;
        } catch {
          const target = this.images.find((i) => i.preview === entry.preview);
          if (target) {
            target.uploading = false;
            target.error = "Upload failed";
          }
        }
      }
    },

    removeImage(preview) {
      URL.revokeObjectURL(preview);
      this.images = this.images.filter((i) => i.preview !== preview);
    },

    async submit() {
      if (!this.rating || !this.name.trim() || !this.email.trim() || !this.review.trim()) {
        this.error = "Please fill in all required fields.";
        return;
      }
      if (this.website.trim()) {
        this.error = "Review submission blocked.";
        return;
      }
      if (this.review.trim().length < 10) {
        this.error = "Please write at least 10 characters.";
        return;
      }
      this.submitting = true;
      this.error = null;
      const tokens = this.images.filter((i) => i.token).map((i) => JSON.stringify(i.token));

      const params = new URLSearchParams();
      params.set("action", "cr_submit_review");
      params.set("id", String(productId));
      params.set("rating", String(this.rating));
      params.set("review", this.review.trim());
      params.set("name", this.name.trim());
      params.set("email", this.email.trim());
      tokens.forEach((t) => params.append("cr-upload-images-ids[]", t));

      try {
        const res = await fetch(cfg.ajaxUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
          body: params.toString(),
        });
        const data = await res.json().catch(() => null);
        if (data && data.code === 0) {
          this.submitted = true;
        } else {
          // Fallback: native WordPress review form.
          const form = new URLSearchParams();
          form.set("rating", String(this.rating));
          form.set("comment", this.review.trim());
          form.set("author", this.name.trim());
          form.set("email", this.email.trim());
          form.set("comment_post_ID", String(productId));
          form.set("comment_parent", "0");
          tokens.forEach((t) => form.append("cr-upload-images-ids[]", t));
          const fallback = await fetch(`${cfg.home}wp-comments-post.php`, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: form.toString(),
            redirect: "follow",
          });
          if (fallback.ok) {
            this.submitted = true;
          } else {
            throw new Error((data && data.description) || "The review form is currently rejecting new submissions.");
          }
        }
      } catch (e) {
        this.error = e.message || "Something went wrong";
      } finally {
        this.submitting = false;
      }
    },
  };
}
