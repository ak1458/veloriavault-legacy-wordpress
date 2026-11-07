import test from "node:test";
import assert from "node:assert/strict";
import { existsSync, readFileSync, readdirSync, statSync } from "node:fs";
import { join } from "node:path";

const root = join(import.meta.dirname, "..");
const assets = readFileSync(join(root, "inc/assets.php"), "utf8");

function phpFiles(dir, files = []) {
  for (const name of readdirSync(dir)) {
    if (["assets", "node_modules", "tools"].includes(name)) continue;
    const path = join(dir, name);
    if (statSync(path).isDirectory()) phpFiles(path, files);
    else if (path.endsWith(".php")) files.push(path);
  }
  return files;
}

test("theme exposes portable asset resolvers", () => {
  assert.match(assets, /function veloria_theme_asset\s*\(/);
  assert.match(assets, /function veloria_asset_url\s*\(/);
  assert.match(assets, /veloria_theme_asset\(\s*'images\/bag-placeholder\.svg'/);
});

test("bundled design assets exist", () => {
  for (const file of [
    "logo.svg",
    "icon.svg",
    "images/og-image.jpg",
    "images/covers/shop.png",
    "images/covers/about.png",
    "images/covers/contact us.png",
    "images/hero/Ultra_realistic_luxury_202603301304.png",
    "images/hero/Ultra_realistic_luxury_202603301304 (1).png",
    "images/hero/Ultra_realistic_luxury_202603301309.png",
    "images/hero/Ultra_realistic_luxury_202603301309 (1).png",
    "images/payment/visa-logo.svg",
    "images/payment/mastercard-logo.svg",
    "images/payment/rupay-logo.svg",
    "images/payment/upi-logo.svg",
    "images/payment/razorpay-logo.svg",
    "images/bag-placeholder.svg",
  ]) assert.equal(existsSync(join(root, "public", file)), true, file);
});

test("templates use portable asset helpers", () => {
  const source = phpFiles(root)
    .filter((file) => !file.endsWith(join("inc", "assets.php")))
    .map((file) => readFileSync(file, "utf8"))
    .join("\n");

  assert.doesNotMatch(source, /home_url\(\s*['"]\/images\//);
  assert.doesNotMatch(source, /home_url\(\s*['"]\/wp-content\/uploads\/2026\/01\/Bag-(?:3-5|8-3|16-4)-scaled\.jpg/);
  assert.match(source, /veloria_asset_url\(/);
  assert.match(source, /veloria_theme_asset\(/);
});

test("homepage relies on the shared main landmark", () => {
  const header = readFileSync(join(root, "header.php"), "utf8");
  const footer = readFileSync(join(root, "footer.php"), "utf8");
  const homepage = readFileSync(join(root, "front-page.php"), "utf8");

  assert.match(header, /<main\s+id=["']main-content["']/);
  assert.equal((header.match(/<main\b/g) || []).length, 1);
  assert.equal((footer.match(/<\/main>/g) || []).length, 1);
  assert.equal((homepage.match(/<main\b/g) || []).length, 0);
  assert.equal((homepage.match(/<\/main>/g) || []).length, 0);

  for (const file of phpFiles(root)) {
    if (file.endsWith("header.php")) continue;
    assert.equal((readFileSync(file, "utf8").match(/<main\b/g) || []).length, 0, file);
  }
});
