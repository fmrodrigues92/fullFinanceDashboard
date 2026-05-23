#!/usr/bin/env python3
"""PreToolUse guard (matcher: Bash).

Bloqueia chamadas diretas a binários que devem rodar via Laravel Sail.
Lê o JSON do PreToolUse no stdin; exit 2 bloqueia a ferramenta e mostra a mensagem ao agente.
"""
import json
import re
import sys

BANNED = {"php", "composer", "npm", "npx", "artisan", "pint", "pest", "phpunit", "yarn", "pnpm"}
SEPARATORS = re.compile(r"&&|\|\||;|\|")
ENV_ASSIGN = re.compile(r"^[A-Za-z_][A-Za-z0-9_]*=")


def main() -> int:
    try:
        data = json.load(sys.stdin)
    except Exception:
        return 0  # entrada inesperada: não bloqueia

    command = data.get("tool_input", {}).get("command", "")
    if not command:
        return 0

    for segment in SEPARATORS.split(command):
        tokens = segment.strip().split()
        i = 0
        while i < len(tokens) and ENV_ASSIGN.match(tokens[i]):
            i += 1  # ignora atribuições de env iniciais (FOO=bar cmd)
        if i >= len(tokens):
            continue
        base = tokens[i].split("/")[-1]
        if base in BANNED:
            sys.stderr.write(
                f"BLOQUEADO: '{base}' deve rodar via Sail.\n"
                f"Use: ./vendor/bin/sail {base} ...\n"
            )
            return 2

    return 0


if __name__ == "__main__":
    sys.exit(main())
