#!/usr/bin/env python3
"""PreToolUse guard (matcher: Bash).

Regras:
1. Bloqueia binários que devem rodar via Sail (php, composer, npm, artisan …).
2. Bloqueia comandos que apagam/resetam o banco de dados:
   migrate:fresh, migrate:reset, db:wipe — mesmo quando chamados via Sail.
"""
import json
import re
import sys

BANNED_BINS = {"php", "composer", "npm", "npx", "artisan", "pint", "pest", "phpunit", "yarn", "pnpm"}

# Subcomandos artisan que destroem dados do banco
DB_WIPE_CMDS = {"migrate:fresh", "migrate:reset", "db:wipe"}

SEPARATORS = re.compile(r"&&|\|\||;|\|")
ENV_ASSIGN = re.compile(r"^[A-Za-z_][A-Za-z0-9_]*=")


def tokens_of(segment: str) -> list[str]:
    parts = segment.strip().split()
    i = 0
    while i < len(parts) and ENV_ASSIGN.match(parts[i]):
        i += 1
    return parts[i:]


def is_db_wipe(tokens: list[str]) -> bool:
    """Detecta se o segmento chama um subcomando artisan que limpa o banco."""
    for wipe_cmd in DB_WIPE_CMDS:
        if wipe_cmd in tokens:
            return True
    return False


def main() -> int:
    try:
        data = json.load(sys.stdin)
    except Exception:
        return 0

    command = data.get("tool_input", {}).get("command", "")
    if not command:
        return 0

    for segment in SEPARATORS.split(command):
        tokens = tokens_of(segment)
        if not tokens:
            continue

        base = tokens[0].split("/")[-1]

        # Regra 1 — binário proibido sem Sail
        if base in BANNED_BINS:
            sys.stderr.write(
                f"BLOQUEADO: '{base}' deve rodar via Sail.\n"
                f"Use: ./vendor/bin/sail {base} ...\n"
            )
            return 2

        # Regra 2 — comandos que apagam o banco (mesmo via Sail)
        if is_db_wipe(tokens):
            matched = next(c for c in DB_WIPE_CMDS if c in tokens)
            sys.stderr.write(
                f"BLOQUEADO: '{matched}' destrói dados do banco e está proibido.\n"
                "Para recriar o schema em teste use: ./vendor/bin/sail artisan migrate:fresh --env=testing\n"
                "Se precisar mesmo assim, peça aprovação explícita ao usuário antes de executar.\n"
            )
            return 2

    return 0


if __name__ == "__main__":
    sys.exit(main())
