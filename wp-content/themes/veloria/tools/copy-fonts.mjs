/**
 * Copies the self-hosted font files (latin subset, same faces next/font
 * loaded on the headless site) into assets/fonts.
 *
 *  - Inter (variable, normal)          → --font-sans
 *  - Playfair Display (variable, normal only: next/font loaded no italic, so
 *    italic text is browser-synthesised exactly as before) → --font-serif
 *  - Lato 300 / 400 / 700 normal       → --font-lato (body text)
 */
import { copyFileSync, mkdirSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const out = join(root, "assets/fonts");
mkdirSync(out, { recursive: true });

const files = [
  ["@fontsource-variable/inter/files/inter-latin-wght-normal.woff2", "inter-latin-wght-normal.woff2"],
  ["@fontsource-variable/playfair-display/files/playfair-display-latin-wght-normal.woff2", "playfair-display-latin-wght-normal.woff2"],
  ["@fontsource/lato/files/lato-latin-300-normal.woff2", "lato-latin-300-normal.woff2"],
  ["@fontsource/lato/files/lato-latin-400-normal.woff2", "lato-latin-400-normal.woff2"],
  ["@fontsource/lato/files/lato-latin-700-normal.woff2", "lato-latin-700-normal.woff2"],
];

for (const [from, to] of files) {
  copyFileSync(join(root, "node_modules", from), join(out, to));
}
console.log(`${files.length} font files copied to assets/fonts`);
