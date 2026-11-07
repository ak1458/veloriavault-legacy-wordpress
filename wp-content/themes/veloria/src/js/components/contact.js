/**
 * Contact form (src/app/contact-us/page.tsx). The headless form only
 * simulated a send; this one delivers the message to the support inbox.
 */
import { rest } from "../lib/api.js";

export function contact() {
  return {
    form: { name: "", email: "", subject: "", message: "" },
    submitting: false,
    submitted: false,
    error: null,
    async submit() {
      this.submitting = true;
      this.error = null;
      const { ok, data } = await rest("contact", { method: "POST", body: this.form });
      this.submitting = false;
      if (ok) {
        this.submitted = true;
        this.form = { name: "", email: "", subject: "", message: "" };
      } else {
        this.error = data.error || "Something went wrong. Please try again.";
      }
    },
  };
}
