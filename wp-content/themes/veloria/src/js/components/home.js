/**
 * Homepage: hero slider (PremiumHero.tsx), reviews slider
 * (CustomerReviewsSection.tsx) and the "Most loved" tabs (LegacyHomeTabs.tsx).
 */
import gsap from "gsap";

const SLIDE_DURATION = 6000;

export function heroSlider(count) {
  return {
    current: 0,
    autoplay: true,
    timer: null,
    resumeTimer: null,
    progressKey: 0,
    init() {
      this.start();
      // Slides after the first only get their photo (via CSS) once the page has loaded.
      const ready = () => this.$el.classList.add("is-ready");
      if (document.readyState === "complete") ready();
      else window.addEventListener("load", ready, { once: true });
      const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      if (!reduce) {
        gsap
          .timeline({ defaults: { ease: "power4.out" } })
          .fromTo(this.$refs.title, { y: 40, opacity: 0 }, { y: 0, opacity: 1, duration: 1 })
          .fromTo(this.$refs.subtitle, { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8 }, "-=0.6");
      }
      this.$watch("current", () => {
        this.progressKey++;
        if (!reduce && this.$refs.title) {
          gsap.fromTo(this.$refs.title, { opacity: 0.7 }, { opacity: 1, duration: 0.4, ease: "power2.out" });
        }
      });
      this.$watch("autoplay", (on) => (on ? this.start() : this.stop()));
    },
    start() {
      this.stop();
      this.timer = setInterval(() => this.next(), SLIDE_DURATION);
    },
    stop() {
      if (this.timer) clearInterval(this.timer);
      this.timer = null;
    },
    next() {
      this.current = (this.current + 1) % count;
    },
    prev() {
      this.current = (this.current - 1 + count) % count;
    },
    goTo(index) {
      this.current = index;
      this.autoplay = false;
      clearTimeout(this.resumeTimer);
      this.resumeTimer = setTimeout(() => (this.autoplay = true), 10000);
    },
    counter() {
      return `${String(this.current + 1).padStart(2, "0")} / ${String(count).padStart(2, "0")}`;
    },
  };
}

export function reviewsSlider(count) {
  return {
    index: 0,
    init() {
      if (count > 1) setInterval(() => (this.index = (this.index + 1) % count), 5000);
    },
  };
}

export function homeTabs(firstSlug) {
  return {
    active: firstSlug,
    scroll(direction) {
      const el = this.$refs["row-" + this.active];
      if (el) el.scrollBy({ left: direction === "left" ? -320 : 320, behavior: "smooth" });
    },
  };
}
