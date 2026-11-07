import test from "node:test";
import assert from "node:assert/strict";
import { existsSync, readFileSync } from "node:fs";
import { join } from "node:path";

const root = import.meta.dirname;

test("reproducibility files are present", () => {
  for (const file of [
    ".nvmrc",
    "Makefile",
    "Dockerfile",
    ".dockerignore",
    "README.md",
    "wp-content/plugins/veloria-commerce/tests/parity/vectors.json",
  ]) {
    assert.equal(existsSync(join(root, "..", file)), true, file);
  }
});

test("runtime and test commands are pinned", () => {
  assert.equal(readFileSync(join(root, "..", ".nvmrc"), "utf8").trim(), "24.19.0");
  const makefile = readFileSync(join(root, "..", "Makefile"), "utf8");
  assert.match(makefile, /^test:/m);
  assert.match(makefile, /node --test/);
  assert.match(makefile, /npm run build/);
  assert.match(makefile, /asset-resolver-contract\.php/);
  assert.match(makefile, /php -l/);
  assert.match(makefile, /run-parity\.php/);
});

test("docker image contains the project build tools", () => {
  const dockerfile = readFileSync(join(root, "..", "Dockerfile"), "utf8");
  for (const packageName of ["php-cli", "zip", "make"]) {
    assert.match(dockerfile, new RegExp(`\\b${packageName}\\b`));
  }
});
