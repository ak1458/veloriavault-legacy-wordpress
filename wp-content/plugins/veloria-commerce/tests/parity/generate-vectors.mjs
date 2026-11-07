/**
 * Generates golden test vectors from the ORIGINAL TypeScript discount engine
 * (src/lib/coupon-calculator.ts in the Next.js repo) so the PHP port can be
 * checked against it to the paisa.
 *
 * Usage:
 *   node generate-vectors.mjs <path-to-next-repo> [count] > vectors.json
 *
 * The coupon list (config/coupons.ts, currently all paused) is swapped for
 * coupons.fixture.json so every coupon branch is exercised.
 */
import { build } from "esbuild";
import { readFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import { tmpdir } from "node:os";
import { writeFileSync } from "node:fs";

const here = dirname(fileURLToPath(import.meta.url));
const repo = process.argv[2];
const count = Number(process.argv[3] || 10000);
if (!repo) {
  console.error("usage: node generate-vectors.mjs <next-repo> [count]");
  process.exit(1);
}

const fixture = readFileSync(join(here, "coupons.fixture.json"), "utf8");

const result = await build({
  entryPoints: [join(repo, "src/lib/coupon-calculator.ts")],
  bundle: true,
  format: "esm",
  platform: "node",
  write: false,
  plugins: [
    {
      name: "alias",
      setup(b) {
        b.onResolve({ filter: /^@\/config\/coupons$/ }, () => ({ path: "coupons", namespace: "fixture" }));
        b.onLoad({ filter: /.*/, namespace: "fixture" }, () => ({
          contents: `export const AVAILABLE_COUPONS = ${fixture};`,
          loader: "js",
        }));
        b.onResolve({ filter: /^@\// }, (args) => {
          const base = join(repo, "src", args.path.slice(2));
          for (const ext of [".ts", ".tsx", "/index.ts"]) {
            try {
              readFileSync(base + ext);
              return { path: base + ext };
            } catch {}
          }
          return { path: base + ".ts" };
        });
      },
    },
  ],
});

const tmp = join(tmpdir(), `veloria-calc-${process.pid}.mjs`);
writeFileSync(tmp, result.outputFiles[0].text);
const { calculateDiscounts, validateCoupon } = await import(pathToFileURL(tmp).href);

// Deterministic PRNG (mulberry32) so runs are reproducible.
let seed = 0x5eed1234;
const rand = () => {
  seed |= 0; seed = (seed + 0x6d2b79f5) | 0;
  let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
};
const pick = (arr) => arr[Math.floor(rand() * arr.length)];

const PRICES = [1499, 1999, 2099, 2199, 2249, 2699, 2799, 2899, 3799, 999.5, 1234.75, 149, 1, 2, 3, 7, 13, 99999];
const CODES = JSON.parse(fixture).map((c) => c.code).concat(["NOPE", "luckydraw", "f&f"]);

const vectors = [];
for (let i = 0; i < count; i++) {
  const lines = 1 + Math.floor(rand() * 4);
  const items = Array.from({ length: lines }, (_, k) => ({
    id: k + 1,
    name: `Bag ${k}`,
    slug: `bag-${k}`,
    image: "",
    category: "Bags",
    price: pick(PRICES),
    quantity: 1 + Math.floor(rand() * 3),
  }));
  const codes = rand() < 0.5 ? [] : Array.from({ length: 1 + Math.floor(rand() * 3) }, () => pick(CODES));
  const isPrepaid = rand() < 0.6;
  const output = calculateDiscounts({ items, appliedCouponCodes: codes, isPrepaid, luckyDrawDiscount: 10 });
  const validateCode = pick(CODES);
  const existing = rand() < 0.5 ? [] : [pick(CODES)];
  const subtotal = items.reduce((s, it) => s + it.price * it.quantity, 0);
  const qty = items.reduce((s, it) => s + it.quantity, 0);
  const validation = validateCoupon(validateCode, subtotal, qty, 10, existing);
  vectors.push({
    input: { items: items.map(({ price, quantity }) => ({ price, quantity })), codes, isPrepaid },
    output,
    validate: { code: validateCode, subtotal, qty, existing, result: { valid: validation.valid, error: validation.error ?? null } },
  });
}

process.stdout.write(JSON.stringify(vectors));
