"""
Captures the policy page text exactly as the live headless site renders it
(the wording lives in the Next.js source, not in WordPress), so it can be
seeded into the WordPress pages:

    python -I capture-policy-content.py <output-dir>
    wp veloria seed-pages --apply --content-dir=<output-dir>

Read-only: plain GET requests to the public pages.
"""
import sys
import urllib.request
from html.parser import HTMLParser
from pathlib import Path

PAGES = ["privacy-policy", "terms-conditions", "shipping-policy", "refund-returns", "cancellation-refund", "warranty-policy"]
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36"
VOID = {"br", "img", "hr", "input", "meta", "link", "source", "wbr"}


class ProseExtractor(HTMLParser):
    """Collects the inner HTML of the first <div class="prose prose-lg ...">."""

    def __init__(self):
        super().__init__(convert_charrefs=False)
        self.depth = 0
        self.capturing = False
        self.done = False
        self.parts = []

    def handle_starttag(self, tag, attrs):
        if self.done:
            return
        cls = dict(attrs).get("class", "") or ""
        if not self.capturing and tag == "div" and "prose" in cls.split() and "prose-lg" in cls.split():
            self.capturing = True
            self.depth = 1
            return
        if self.capturing:
            self.parts.append(self.get_starttag_text())
            if tag not in VOID:
                self.depth += 1

    def handle_startendtag(self, tag, attrs):
        if self.capturing and not self.done:
            self.parts.append(self.get_starttag_text())

    def handle_endtag(self, tag):
        if not self.capturing or self.done or tag in VOID:
            return
        self.depth -= 1
        if self.depth == 0:
            self.done = True
            self.capturing = False
        else:
            self.parts.append(f"</{tag}>")

    def handle_data(self, data):
        if self.capturing and not self.done:
            self.parts.append(data)

    def handle_entityref(self, name):
        if self.capturing and not self.done:
            self.parts.append(f"&{name};")

    def handle_charref(self, name):
        if self.capturing and not self.done:
            self.parts.append(f"&#{name};")


def main(out_dir: str) -> None:
    out = Path(out_dir)
    out.mkdir(parents=True, exist_ok=True)
    for slug in PAGES:
        req = urllib.request.Request(f"https://www.veloriavault.com/{slug}", headers={"User-Agent": UA})
        html = urllib.request.urlopen(req, timeout=30).read().decode("utf-8")
        parser = ProseExtractor()
        parser.feed(html)
        content = "".join(parser.parts).strip()
        # Internal links: keep them relative to the site.
        content = content.replace('href="https://www.veloriavault.com/', 'href="/')
        (out / f"{slug}.html").write_text(content + "\n", encoding="utf-8")
        print(f"{slug}: {len(content)} chars")


if __name__ == "__main__":
    main(sys.argv[1] if len(sys.argv) > 1 else "content")
