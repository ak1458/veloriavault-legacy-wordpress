/** Verify literal template and storefront-script classes against built CSS. */
import { readFileSync, readdirSync, statSync } from "node:fs";
import { join, dirname, extname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");

function walk(dir, out = []) {
  for (const name of readdirSync(dir)) {
    if (["node_modules", "assets", "tools"].includes(name)) continue;
    const path = join(dir, name);
    if (statSync(path).isDirectory()) walk(path, out);
    else if ([".php", ".js"].includes(extname(path))) out.push(path);
  }
  return out;
}

/** Read a PHP/JS quoted literal without interpreting or executing its code. */
function quoted(source, start) {
  const quote = source[start];
  let value = "";
  let i = start + 1;
  for (; i < source.length; i++) {
    if (source[i] === "\\" && i + 1 < source.length) {
      const next = source[++i];
      value += next === quote || next === "\\" ? next : "\\" + next;
    } else if (source[i] === quote) return { value, end: i + 1 };
    else value += source[i];
  }
  return { value, end: i };
}

/** Separate PHP from markup, including HTML embedded in PHP string literals. */
function separatePhp(source) {
  const regions = [];
  const strings = [];
  let markup = "";
  let cursor = 0;
  const opening = /<\?(?:php\b|=)/g;
  let match;
  while ((match = opening.exec(source))) {
    markup += source.slice(cursor, match.index);
    const start = opening.lastIndex;
    let i = start;
    for (; i < source.length && source.slice(i, i + 2) !== "?>";) {
      if (source[i] === "'" || source[i] === '"') {
        const literal = quoted(source, i);
        strings.push(literal.value);
        i = literal.end;
      } else if (source.slice(i, i + 2) === "/*") {
        const end = source.indexOf("*/", i + 2);
        i = end === -1 ? source.length : end + 2;
      } else if (source.slice(i, i + 2) === "//" || source[i] === "#") {
        while (i < source.length && source[i] !== "\n" && source.slice(i, i + 2) !== "?>") i++;
      } else i++;
    }
    regions.push((match[0] === "<?=" ? "echo " : "") + source.slice(start, i));
    markup += `__VELORIA_PHP_${regions.length - 1}__`;
    cursor = i < source.length ? i + 2 : i;
    opening.lastIndex = cursor;
  }
  return { markup: markup + source.slice(cursor), regions, strings };
}

function tokens(expression) {
  const out = [];
  for (let i = 0; i < expression.length;) {
    if (/\s/.test(expression[i])) { i++; continue; }
    if (expression[i] === "'" || expression[i] === '"') {
      const literal = quoted(expression, i);
      out.push({ string: literal.value }); i = literal.end;
    } else {
      const match = expression.slice(i).match(/^(?:\?\?|\|\||&&|===|!==|==|!=|=>|[\w$]+|.)/s);
      out.push(match[0]); i += match[0].length;
    }
  }
  return out;
}

/** Find delimiters outside nested calls, arrays and object expressions. */
function topLevel(input, predicate) {
  const positions = [];
  let depth = 0;
  for (let i = 0; i < input.length; i++) {
    const token = input[i];
    if ([")", "]", "}"].includes(token)) depth--;
    if (depth === 0 && predicate(token)) positions.push(i);
    if (["(", "[", "{"].includes(token)) depth++;
  }
  return positions;
}

/** Only strings that can be returned as classes count, not condition values. */
function outputStrings(input) {
  if (!input.length) return [];
  if (input[0] === "(") {
    let depth = 0;
    for (let i = 0; i < input.length; i++) {
      if (input[i] === "(") depth++;
      else if (input[i] === ")") depth--;
      if (depth === 0) {
        if (i === input.length - 1) return outputStrings(input.slice(1, -1));
        break;
      }
    }
  }
  const question = topLevel(input, (token) => token === "?")[0];
  if (question !== undefined) {
    let nested = 0;
    for (const i of topLevel(input, (token) => token === "?" || token === ":")) {
      if (i <= question) continue;
      if (input[i] === "?") nested++;
      else if (nested) nested--;
      else return [...outputStrings(input.slice(question + 1, i)), ...outputStrings(input.slice(i + 1))];
    }
  }
  const logical = topLevel(input, (token) => ["||", "??", "&&"].includes(token));
  if (logical.length) {
    const i = logical[0];
    return [...outputStrings(input.slice(0, i)), ...outputStrings(input.slice(i + 1))];
  }
  if (input[0] === "{" && input.at(-1) === "}") {
    const body = input.slice(1, -1);
    const ends = [...topLevel(body, (token) => token === ","), body.length];
    let start = 0;
    const strings = [];
    for (const end of ends) {
      const entry = body.slice(start, end);
      if (entry[1] === ":") strings.push(typeof entry[0] === "object" ? entry[0].string : entry[0]);
      start = end + 1;
    }
    return strings;
  }
  if (input[0] === "[" && input.at(-1) === "]") {
    const body = input.slice(1, -1);
    const ends = [...topLevel(body, (token) => token === ","), body.length];
    let start = 0;
    return ends.flatMap((end) => { const result = outputStrings(body.slice(start, end)); start = end + 1; return result; });
  }
  return input.length === 1 && typeof input[0] === "object" ? [input[0].string] : [];
}

function classAttributes(markup, regions = []) {
  const classes = [];
  const attributes = /(?:^|[\s<])(class|:class|x-bind:class)\s*=\s*(["'])([\s\S]*?)\2/g;
  for (const match of markup.matchAll(attributes)) {
    const value = match[3];
    if (match[1] !== "class") classes.push(...outputStrings(tokens(value)));
    else {
      classes.push(value.replace(/__VELORIA_PHP_\d+__/g, " "));
      for (const php of value.matchAll(/__VELORIA_PHP_(\d+)__/g)) {
        const code = regions[Number(php[1])] || "";
        const echo = code.match(/^\s*echo\s+([\s\S]*?);?\s*$/);
        if (echo) classes.push(...outputStrings(tokens(echo[1].replace(/;\s*$/, ""))));
      }
    }
  }
  return classes;
}

export function extractClasses(source) {
  const { markup, regions, strings } = separatePhp(source);
  const lists = [...classAttributes(markup, regions), ...strings.flatMap((literal) => classAttributes(literal))];
  return new Set(lists.flatMap((list) => list.split(/\s+/)).filter((cls) =>
    cls && !cls.includes("__VELORIA_PHP_") && !cls.includes("$") && !/^%(?:\d+\$)?[-+0 #]*(?:\d+)?(?:\.\d+)?[sdf]$/.test(cls)));
}

// Existing non-utility hooks, markers and known unsupported legacy classes.
// New missing utilities must fail; do not add them here to pass builds.
const ignore = new Set(["group", "peer", "lucide", "container", "market-header", "scrollbar-style", "legacy-home-page", "veloria-pay", "veloria-wc-notices", "veloria-wc-form", "notice", "notice-error", "prose-headings:font-serif", "prose-a:text-[#b59a5c]", "hover:prose-a:text-[#a08a4f]", "[&_p]:m-0]", "prose-sm", "prose-lg", "clear"]);
const escapeSelector = (cls) => cls.replace(/([^a-zA-Z0-9_-])/g, "\\$1").replace(/^(\d)/, "\\3$1 ");
const escapeRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

export function missingClasses(classes, css) {
  return [...classes].filter((cls) => {
    if (ignore.has(cls) || cls.startsWith("lucide-") || cls.startsWith("chat-") || cls.startsWith("group/") || cls.startsWith("veloria-hero")) return false;
    // A prefix is not a match: .bg-black/90 must not satisfy bg-black.
    return !new RegExp("\\." + escapeRegex(escapeSelector(cls)) + "(?![a-zA-Z0-9_\\\\-])").test(css);
  });
}

export function verifyCss(themeRoot = root) {
  const css = readFileSync(join(themeRoot, "assets/dist/app.css"), "utf8");
  const missing = new Map();
  for (const file of walk(themeRoot)) {
    for (const cls of missingClasses(extractClasses(readFileSync(file, "utf8")), css)) {
      if (!missing.has(cls)) missing.set(cls, file.replace(themeRoot, ""));
    }
  }
  return missing;
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const missing = verifyCss();
  if (missing.size) {
    for (const [cls, file] of missing) console.log(`MISSING ${cls}  (${file})`);
    process.exitCode = 1;
  } else console.log("All template classes present in app.css");
}
