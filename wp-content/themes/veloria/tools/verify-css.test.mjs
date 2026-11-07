import test from "node:test";
import assert from "node:assert/strict";
import { extractClasses, missingClasses, verifyCss } from "./verify-css.mjs";
import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from "node:fs";
import { join } from "node:path";
import { tmpdir } from "node:os";
const classes = (source) => [...extractClasses(source)].sort();

test("static attributes support both HTML quotes and exact names", () => {
  assert.deepEqual(classes(`<div class="flex text-sm" data-class="fake" x-class="fake"><p class='p-4'></p>`), ["flex", "p-4", "text-sm"]);
});
test("PHP expressions cannot become class tokens", () => {
  assert.deepEqual(classes(`<div class="flex <?php echo esc_attr( $initial ); ?> text-sm">`), ["flex", "text-sm"]);
});
test("PHP class-producing ternary branches are still checked", () => {
  assert.deepEqual(classes(`<div class="flex <?php echo $active ? 'bg-black' : 'bg-white'; ?>">`), ["bg-black", "bg-white", "flex"]);
});
test("HTML strings in PHP are checked without concatenation syntax", () => {
  assert.deepEqual(classes(`<?php echo '<div class="notice text-red-500">'; $x = '<svg class="%s">'; ?>`), ["notice", "text-red-500"]);
});
test("quoted PHP closing tags do not terminate PHP early", () => {
  assert.deepEqual(classes(`<?php $x = "?>"; ?><div class="flex <?php echo esc_attr( $x ); ?>">`), ["flex"]);
});
test("Alpine ternary compares values without treating them as classes", () => {
  assert.deepEqual(classes(`<div :class="message && message.type === 'success' ? 'text-green-400' : 'text-red-400'">`), ["text-green-400", "text-red-400"]);
});
test("PHP interpolation in Alpine comparisons is ignored", () => {
  assert.deepEqual(classes(`<span :class="'<?php echo esc_js( $icon ); ?>' === 'loader-2' && index === 1 ? 'animate-spin' : ''">`), ["animate-spin"]);
});
test("Alpine objects check class keys, not predicate values", () => {
  assert.deepEqual(classes(`<div x-bind:class="{ 'bg-black text-white': type === 'ok', hidden: !open }">`), ["bg-black", "hidden", "text-white"]);
});
test("Alpine arrays, nested ternaries and logical branches remain checked", () => {
  assert.deepEqual(classes(`<div :class="['flex', ready && (dark ? 'bg-black' : 'bg-white'), state === 'ok' ? 'p-4' : failed ? 'm-2' : 'm-0']">`), ["bg-black", "bg-white", "flex", "m-0", "m-2", "p-4"]);
});
test("helper arguments and condition literals are not output classes", () => {
  assert.deepEqual(classes(`<div :class="statusColor('pending')"><span :class="type === 'user' && enabled">`), []);
});
test("real missing utilities still fail and escaped exact selectors pass", () => {
  assert.deepEqual(missingClasses(extractClasses(`<div class="flex bg-opacity-90 hover:bg-black/90">`), `.flex{display:flex}.hover\\:bg-black\\/90:hover{background:black}`), ["bg-opacity-90"]);
});
test("a longer selector cannot satisfy a missing shorter utility", () => {
  assert.deepEqual(missingClasses(["p-4", "bg-black"], `.p-40{padding:10rem}.bg-black\\/90{background:black}`), ["p-4", "bg-black"]);
});

test("verifyCss scans PHP and JavaScript template sources", () => {
  const root = mkdtempSync(join(tmpdir(), "veloria-css-"));
  try {
    mkdirSync(join(root, "assets", "dist"), { recursive: true });
    mkdirSync(join(root, "src"), { recursive: true });
    writeFileSync(join(root, "assets", "dist", "app.css"), ".flex{display:flex}.text-red-500{color:red}");
    writeFileSync(join(root, "template.php"), '<div class="flex"></div>');
    writeFileSync(join(root, "src", "app.js"), 'const markup = `<div class="text-red-500"></div>`;');
    assert.equal(verifyCss(root).size, 0);

    writeFileSync(join(root, "assets", "dist", "app.css"), ".flex{display:flex}");
    const missing = verifyCss(root);
    assert.equal(missing.get("text-red-500").endsWith(join("src", "app.js")), true);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});
