#!/usr/bin/env python3
"""PreToolUse path guard (matcher: Write|Edit).

Restringe ONDE cada papel pode escrever. Aplica-se apenas a chamadas vindas de subagents
conhecidos (campo `agent_type` do payload); chamadas da thread principal não são restringidas.
Lê o JSON do PreToolUse no stdin; exit 2 bloqueia a ferramenta e mostra a mensagem ao agente.
"""
import json
import os
import sys

# Prefixos de escrita permitidos por papel (relativos à raiz do projeto).
POLICIES = {
    "gerente": ["docs/specs/", "docs/progress/", "docs/contracts/"],
    "backend": ["app/", "routes/", "database/", "tests/"],
    "tester": ["tests/Unit/"],
    "frontend": ["resources/js/"],
    "auditor": ["docs/audits/", "docs/progress/"],
}


def main() -> int:
    try:
        data = json.load(sys.stdin)
    except Exception:
        return 0

    agent = data.get("agent_type")
    allowed = POLICIES.get(agent)
    if not allowed:
        return 0  # thread principal ou agente sem política: não restringe aqui

    path = data.get("tool_input", {}).get("file_path", "")
    if not path:
        return 0

    project_dir = os.environ.get("CLAUDE_PROJECT_DIR") or data.get("cwd") or os.getcwd()
    rel = os.path.relpath(os.path.abspath(path), project_dir).replace(os.sep, "/")

    if rel.startswith("../"):
        sys.stderr.write(f"BLOQUEADO: o papel '{agent}' tentou escrever fora do projeto ({rel}).\n")
        return 2

    if any(rel.startswith(prefix) for prefix in allowed):
        return 0

    sys.stderr.write(
        f"BLOQUEADO: o papel '{agent}' não pode escrever em '{rel}'.\n"
        f"Caminhos permitidos para este papel: {', '.join(allowed)}\n"
    )
    return 2


if __name__ == "__main__":
    sys.exit(main())
