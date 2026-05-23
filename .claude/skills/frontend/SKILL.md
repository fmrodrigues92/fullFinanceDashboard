---
name: frontend
description: Especialista frontend do fullFinanceDashboard. Use para construir a UI de uma feature a partir do contrato em docs/contracts/ (páginas Inertia em React 19 + TypeScript tipado, rotas Wayfinder, componentes shadcn/ui). Acione com "construir a tela/página", "fazer o frontend da feature X", "consumir o contrato".
---

# Especialista Frontend

> Fonte única da verdade deste papel. O subagent `frontend` apenas executa este playbook em isolamento.

**Missão:** construir a UI consumindo o contrato. Os tipos TypeScript espelham o contrato 1:1.

## Wiring (SDD)
- **Entrada:** `docs/contracts/{feature}.md` (se ausente, pare e peça o /backend; nunca deduza a API).
- **Saída:** páginas em `resources/js/pages/{Context}/` + tipos em `resources/js/types/`.
- **Handoff:** reportar divergências de contrato ao /backend.
- **Escopo de escrita:** apenas `resources/js/`.

## Invariantes (ver CLAUDE.md)
- **Inertia é o transporte:** dados chegam como **props de página** (`usePage().props`), não via fetch; forms via
  `useForm`/`router` do Inertia. JSON só para clientes de API.
- Rotas via Wayfinder (sem URL hardcoded). UI shadcn/ui. Tratar 403/422 do contrato. Sempre Sail. Nunca commitar.

## Desacoplamento (pode virar projeto independente)
- Dependa **só do contrato** (`docs/contracts/`) — nunca de internals/banco do backend.
- Isole o acesso a dados atrás de uma fronteira fina: hoje props Inertia; amanhã pode ser um cliente HTTP/JSON.
  Páginas e components consomem **tipos do contrato**, não o mecanismo de transporte. "Inertia-acoplado agora, extraível depois."
- Os tipos em `resources/js/types/` derivam do contrato; são o ponto de troca se o backend virar API externa.

## Layouts comutáveis (`.env` ou header) — testar sem quebrar o atual
- **Default por `.env`:** `APP_LAYOUT` (lido via `config('app.layout')`).
- **Override por header:** `X-Layout` na request — QA testa um layout novo enquanto a produção segue no default.
- O backend compartilha o nome do layout como **prop Inertia** (middleware + allowlist); o React escolhe o
  componente por um **registry**. Cada layout é uma **pasta self-contained no topo de `resources/js/`**
  (`layout01`, `layout02`, …); o `layout01` (baseline) re-exporta o `AppLayout` do kit, **sem editá-lo**.
  Código completo em `patterns.md`.

## Padrões de código (exemplos)
Mecanismo de layout comutável + página consumindo contrato: `.claude/skills/frontend/patterns.md`. Copie a forma.

## Processo
1. Leia `docs/contracts/{feature}.md`: shape dos dados, erros (403/422), paginação, estados vazios.
2. Tipos em `resources/js/types/` espelhando o contrato 1:1.
3. Página em `resources/js/pages/{Context}/`: props via `usePage()`, forms via `useForm`, rotas via Wayfinder.
   O layout é aplicado **globalmente** pelo `app.tsx` — a página não define `Page.layout`.
4. Trate todos os estados: loading, vazio, sucesso e os erros do contrato. Sem URL hardcoded.
5. `./vendor/bin/sail npm run types:check` (sem erros) e verifique no navegador (caminho feliz + erros).
6. Divergência com o contrato? **Não improvise** — reporte ao `/backend`.
