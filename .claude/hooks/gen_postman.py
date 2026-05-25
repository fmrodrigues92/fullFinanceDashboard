#!/usr/bin/env python3
"""PostToolUse hook — regenera docs/contracts/postman_collection.json
sempre que um arquivo .md em docs/contracts/ é escrito ou editado.

Formato de saída: Postman Collection v2.1.
Variáveis de ambiente esperadas no Postman:
  base_url    → ex.: http://localhost
  auth_token  → Bearer token de sessão
  csrf_token  → X-CSRF-TOKEN (para mutações via cookie/session)
"""
import json
import os
import re
import sys
from pathlib import Path


def main() -> int:
    try:
        event = json.load(sys.stdin)
    except Exception:
        return 0

    file_path = event.get("tool_input", {}).get("file_path", "")
    project_dir = os.environ.get("CLAUDE_PROJECT_DIR") or event.get("cwd") or os.getcwd()
    contracts_dir = os.path.join(project_dir, "docs", "contracts")

    # Só age em arquivos .md dentro de docs/contracts/ (ignora template e o próprio JSON)
    abs_path = os.path.abspath(file_path)
    if not abs_path.startswith(os.path.abspath(contracts_dir)):
        return 0
    if not abs_path.endswith(".md") or os.path.basename(abs_path).startswith("_"):
        return 0

    collection = build_collection(contracts_dir)
    output = os.path.join(contracts_dir, "postman_collection.json")
    with open(output, "w", encoding="utf-8") as f:
        json.dump(collection, f, indent=2, ensure_ascii=False)

    print(f"[gen_postman] {output} atualizado ({len(collection['item'])} pasta(s))")
    return 0


def build_collection(contracts_dir: str) -> dict:
    folders = []
    for md_file in sorted(Path(contracts_dir).glob("*.md")):
        if md_file.name.startswith("_"):
            continue
        items = parse_contract(md_file)
        if not items:
            continue
        name = md_file.stem.replace("_", " ").replace("-", " ").title()
        folders.append({"name": name, "item": items})

    return {
        "info": {
            "name": "fullFinanceDashboard API",
            "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
            "description": "Auto-gerado de docs/contracts/. Não edite manualmente.",
        },
        "auth": {
            "type": "bearer",
            "bearer": [{"key": "token", "value": "{{auth_token}}", "type": "string"}],
        },
        "variable": [
            {"key": "base_url", "value": "http://localhost", "type": "string"},
            {"key": "auth_token", "value": "", "type": "string"},
            {"key": "csrf_token", "value": "", "type": "string"},
        ],
        "item": folders,
    }


def parse_contract(md_file: Path) -> list[dict]:
    content = md_file.read_text(encoding="utf-8")
    pattern = re.compile(
        r"###\s+(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\s+(/\S*)"
        r"(.*?)(?=###\s+(?:GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\s+/|\Z)",
        re.DOTALL | re.IGNORECASE,
    )
    items = []
    for m in pattern.finditer(content):
        method = m.group(1).upper()
        route = m.group(2).strip()
        block = m.group(3)

        desc_m = re.search(r"\*\*Descrição:\*\*\s*(.+)", block)
        description = desc_m.group(1).strip() if desc_m else ""

        request_body = extract_json_block(block, r"\*\*Request\*\*")
        items.append(build_item(method, route, description, request_body))

    return items


def extract_json_block(text: str, anchor_pattern: str) -> dict | str | None:
    pattern = re.compile(anchor_pattern + r"\s*```json\s*(.*?)```", re.DOTALL)
    m = pattern.search(text)
    if not m:
        return None
    raw = m.group(1).strip()
    try:
        return json.loads(raw)
    except json.JSONDecodeError:
        return raw


def build_item(method: str, route: str, description: str, request_body) -> dict:
    # Extrai variáveis de path: {id} → :id no Postman
    path_vars = re.findall(r"\{(\w+)\}", route)
    postman_raw = re.sub(r"\{(\w+)\}", r":\1", route)

    segments = [s for s in postman_raw.split("/") if s]
    url: dict = {
        "raw": "{{base_url}}" + postman_raw,
        "host": ["{{base_url}}"],
        "path": segments,
    }
    if path_vars:
        url["variable"] = [{"key": v, "value": "", "description": ""} for v in path_vars]

    headers = [
        {"key": "Accept", "value": "application/json"},
        {"key": "Content-Type", "value": "application/json"},
        {"key": "X-CSRF-TOKEN", "value": "{{csrf_token}}"},
    ]

    request: dict = {
        "method": method,
        "header": headers,
        "url": url,
        "description": description,
    }

    if request_body is not None and method in ("POST", "PUT", "PATCH"):
        raw_body = (
            json.dumps(request_body, indent=2, ensure_ascii=False)
            if isinstance(request_body, dict)
            else request_body
        )
        request["body"] = {
            "mode": "raw",
            "raw": raw_body,
            "options": {"raw": {"language": "json"}},
        }

    return {
        "name": f"{method} {route}",
        "request": request,
        "response": [],
    }


if __name__ == "__main__":
    sys.exit(main())
