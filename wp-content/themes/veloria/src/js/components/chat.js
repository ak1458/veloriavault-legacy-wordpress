/**
 * Shopping assistant widget (AgenticChatWidget.tsx).
 */
import { rest } from "../lib/api.js";

const greetingText = () => {
  const hour = new Date().getHours();
  const time = hour < 12 ? "Good morning" : hour < 17 ? "Good afternoon" : "Good evening";
  return `${time}! ✨ Welcome to Veloria Vault. I'm your personal shopping assistant.\n\nI can help you find the perfect leather bag or wallet. What are you looking for today?`;
};

/** Markdown-light: **bold** and line breaks, on escaped text. */
export function formatMessage(text) {
  const escaped = String(text || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
  return escaped.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>").replace(/\n/g, "<br />");
}

export function chatWidget() {
  return {
    input: "",

    get store() {
      return Alpine.store("chat");
    },

    init() {
      this.$watch("store.isOpen", (open) => {
        if (!open) return;
        setTimeout(() => this.$refs.input && this.$refs.input.focus(), 300);
        if (!this.store.hasGreeted) {
          this.store.hasGreeted = true;
          this.store.addMessage({
            role: "assistant",
            content: greetingText(),
            quickReplies: ["💼 Find an Office Bag", "👜 Show Tote Bags", "🎁 Gift Ideas", "🔥 What's on Sale?", "💰 Wallets & Clutches"],
          });
        }
        this.scrollDown();
      });
    },

    scrollDown() {
      this.$nextTick(() => {
        const el = this.$refs.end;
        if (el) el.scrollIntoView({ behavior: "smooth" });
      });
    },

    async send(text) {
      const value = String(text || "").trim();
      if (!value || this.store.isLoading) return;
      this.input = "";
      this.store.addMessage({ role: "user", content: value });
      this.store.isLoading = true;
      this.store.messages.push({ id: "typing", role: "assistant", content: "", isTyping: true });
      this.scrollDown();

      const { ok, data } = await rest("chat", { method: "POST", body: { message: value, sessionId: this.store.sessionId } });
      this.store.removeTyping();
      if (ok) {
        this.store.addMessage({
          role: "assistant",
          content: data.content || "I'm not sure how to help with that. Try browsing our shop!",
          products: data.products,
          quickReplies: data.quickReplies,
        });
      } else {
        this.store.addMessage({
          role: "assistant",
          content: "Sorry, I'm having trouble connecting. Please try again or browse our shop directly.",
          quickReplies: ["Try again", "Browse Shop"],
        });
      }
      this.store.isLoading = false;
      this.scrollDown();
    },

    buy(product) {
      if (product.type === "variable") {
        window.location.assign(product.href);
        return;
      }
      Alpine.store("cart").addItem({
        id: product.id,
        name: product.name,
        slug: product.slug,
        price: parseFloat(product.price),
        image: product.image,
        category: product.category,
        href: product.href,
      });
      this.store.addMessage({
        role: "assistant",
        content: `✅ **${product.name}** added to your cart!\n\nWould you like to checkout or keep browsing?`,
        quickReplies: ["🛒 Go to Cart", "Keep browsing", "Show similar items"],
      });
      Alpine.store("cart").openCart();
      this.scrollDown();
    },

    discount(product) {
      const price = parseFloat(product.price);
      const regular = parseFloat(product.regularPrice);
      return product.onSale && regular > price ? Math.round(((regular - price) / regular) * 100) : 0;
    },

    formatMessage,
  };
}

export function chatCarousel() {
  return {
    canLeft: false,
    canRight: true,
    init() {
      this.$nextTick(() => this.check());
    },
    check() {
      const el = this.$refs.track;
      if (!el) return;
      this.canLeft = el.scrollLeft > 10;
      this.canRight = el.scrollLeft < el.scrollWidth - el.clientWidth - 10;
    },
    scroll(direction) {
      this.$refs.track.scrollBy({ left: direction === "left" ? -220 : 220, behavior: "smooth" });
    },
  };
}
