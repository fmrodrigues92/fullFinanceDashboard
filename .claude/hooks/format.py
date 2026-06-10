#!/usr/bin/env python3
"""PostToolUse formatter (matcher: Write|Edit).

Best-effort: formata o arquivo recém-escrito via Sail (Pint para PHP, Prettier para front).
Se o Sail não estiver de pé, sai em silêncio. Nunca bloqueia (sempre exit 0).
"""
import json
import os
import subprocess
import sys

PROJECT_DIR = os.environ.get("CLAUDE_PROJECT_DIR", os.getcwd())
SAIL = os.path.join(PROJECT_DIR, "vendor", "bin", "sail")


def sail_running() -> bool:
    try:
        out = subprocess.run(
            [SAIL, "ps", "--status", "running"],
            cwd=PROJECT_DIR, capture_output=True, text=True, timeout=15,
        )
        return bool(out.stdout.strip())
    except Exception:
        return False


def main() -> int:
    try:
        data = json.load(sys.stdin)
    except Exception:
        return 0

    path = data.get("tool_input", {}).get("file_path", "")
    if not path or not os.path.isfile(path):
        return 0

    rel = os.path.relpath(path, PROJECT_DIR)
    ext = os.path.splitext(path)[1].lower()

    if not os.path.exists(SAIL) or not sail_running():
        return 0  # ambiente desligado: pula

    try:
        if ext == ".php":
            subprocess.run([SAIL, "pint", rel], cwd=PROJECT_DIR, timeout=120)
        elif ext in {".ts", ".tsx", ".js", ".jsx", ".css"}:
            subprocess.run([SAIL, "npx", "prettier", "--write", rel], cwd=PROJECT_DIR, timeout=120)
    except Exception:
        pass  # formatação é best-effort

    return 0


if __name__ == "__main__":
    sys.exit(main())
