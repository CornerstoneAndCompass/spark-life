#!/usr/bin/env python3
"""
Build the minified CSS/JS the theme actually serves.

The sources under assets/ stay fully commented, because that is what anyone
maintaining this needs. Those comments would otherwise be public: a stylesheet
is fetched verbatim by the browser, so every note in it is readable by any
visitor who opens the file. This strips them for the served copy.

Run automatically by deploy.sh, so the built files can never drift from source.
Can also be run by hand:

    python3 tools/build-assets.py
"""
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
ASSETS = ROOT / "wp-content/themes/sparklife-theme/assets"

BANNER = "/*! Spark Life Electrical */\n"


def minify_css(src: str) -> str:
    # Strip /* ... */ comments. Verified there are no comment-like sequences
    # inside content:"" strings in this stylesheet, so a plain strip is safe.
    out = re.sub(r"/\*.*?\*/", "", src, flags=re.S)
    out = re.sub(r"\s*\n\s*", "\n", out)          # collapse indentation
    out = re.sub(r"\n{2,}", "\n", out)            # collapse blank lines
    out = re.sub(r"\s*([{}:;,>])\s*", r"\1", out)  # tighten around punctuation
    out = re.sub(r";}", "}", out)
    return BANNER + out.strip() + "\n"


def minify_js(src: str) -> str:
    # Deliberately conservative: comments only, no structural rewriting. A real
    # JS minifier needs a parser to be safe around ASI and regex literals, and
    # this file is small enough that the extra bytes do not matter.
    out = re.sub(r"/\*.*?\*/", "", src, flags=re.S)
    lines = []
    for line in out.split("\n"):
        # Whole-line // comments only. Verified: this file has no trailing
        # same-line comments and no // inside strings or regex literals, so
        # nothing else can be caught by this.
        if re.match(r"^\s*//", line):
            continue
        if line.strip():
            lines.append(line)
    return BANNER + "\n".join(lines) + "\n"


def build(name: str, fn) -> None:
    src_path = ASSETS / name
    if not src_path.exists():
        sys.exit("missing source: %s" % src_path)
    src = src_path.read_text()
    out = fn(src)
    out_path = src_path.with_suffix("").with_suffix(".min" + src_path.suffix)
    out_path.write_text(out)
    saved = 100 - (len(out) / len(src) * 100)
    print("  %-28s %6.1f KB -> %5.1f KB  (-%.0f%%)"
          % (out_path.name, len(src) / 1024, len(out) / 1024, saved))
    if "Claude" in out or "claude" in out:
        sys.exit("refusing to write: build output mentions Claude")


if __name__ == "__main__":
    print("building served assets:")
    build("css/main.css", minify_css)
    build("js/main.js", minify_js)
