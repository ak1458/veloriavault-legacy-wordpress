import test from "node:test";
import assert from "node:assert/strict";

globalThis.window = { veloriaConfig: { ajaxUrl: "https://store.test/ajax", home: "https://store.test/" } };
const { reviewModal } = await import("../src/js/components/product.js");

test("redirect to an HTTP error must not report review success", async (t) => {
  let calls = 0;
  t.mock.method(globalThis, "fetch", async () => ++calls === 1
    ? { json: async () => ({ code: 1, description: "Review rejected" }) }
    : { ok: false, redirected: true, status: 403 });
  const form = reviewModal(1);
  Object.assign(form, { rating: 5, name: "Test", email: "test@example.invalid", review: "A test review text." });
  await form.submit();
  assert.equal(form.submitted, false);
  assert.equal(form.error, "Review rejected");
  assert.equal(form.submitting, false);
});

test("removing a photo during upload does not crash completion", async (t) => {
  let complete;
  t.mock.method(globalThis, "fetch", () => new Promise(resolve => { complete = resolve; }));
  const form = reviewModal(1);
  const pending = form.upload([new File(["image"], "review.png", { type: "image/png" })]);
  assert.equal(form.images.length, 1);
  form.removeImage(form.images[0].preview);
  complete({ json: async () => ({ code: 200, attachment: { id: 2, key: "test" } }) });
  await pending;
  assert.deepEqual(form.images, []);
});

test("successful fallback review is acknowledged", async (t) => {
  let calls = 0;
  t.mock.method(globalThis, "fetch", async () => ++calls === 1
    ? { json: async () => null }
    : { ok: true, redirected: true });
  const form = reviewModal(1);
  Object.assign(form, { rating: 5, name: "Test", email: "test@example.invalid", review: "A test review text." });
  await form.submit();
  assert.equal(form.submitted, true);
});
