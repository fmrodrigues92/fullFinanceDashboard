# fullFinanceDashboard

Dashboard financeiro full-stack **multi-usuário**. Cada usuário só acessa os próprios dados.

## Stack
Laravel 13 (PHP 8.4+, DDD) · React 19 + TypeScript + Inertia.js · shadcn/ui (Radix + Tailwind v4) ·
Fortify (2FA, passkeys) · PostgreSQL + Redis · Wayfinder (rotas tipadas) · Pest · Laravel Sail (Docker).

## Invariantes (não negociáveis)
1. **Sail sempre.** Nunca `php`/`composer`/`npm`/`artisan` direto — use `./vendor/bin/sail ...`. _(Hook bloqueia o resto.)_
2. **Isolamento por `user_id`.** Toda tabela de negócio tem FK → users; toda leitura/escrita filtra por `user_id` + Policy.
3. **Nunca commitar sem aprovação explícita.**
4. Se usuário usar chave GPG para assinar commit, deixar o comando pronto em /temp para ele fazer manualmente

## DDD (backend)
- Lógica de negócio em `app/src/{Context}/` (namespace `Src\`), tipicamente `Domain/Application/Infrastructure`.
- Controllers finos em `app/Http/Controllers/{Context}/` — só orquestram; respondem **Inertia** (padrão) ou **JSON** conforme o header.
- Padrões: Service + Repository, SOLID, DRY, TDD.

## Fluxo SDD
`/gerente` (tech-lead — spec → `docs/specs/` **e** contrato → `docs/contracts/`) → `/backend` e `/frontend` **em paralelo**
(ambos consomem o contrato) → `/tester` e `/auditor` **em paralelo** (após o `/backend`).
O **contrato** é a fronteira backend↔frontend; **dono único: `/gerente`**. Detalhes: `docs/README.md`.

`/auditor` — varre **apenas os arquivos da feature** por vulnerabilidades de segurança (OWASP, isolamento
`user_id`, Laravel-specific) e, secundariamente, performance; gera relatório em `docs/audits/`, nunca edita
código, e cataloga correções no `STATUS.md` para o `/backend` aplicar.

## Configuração do agente
- `.claude/skills/{papel}/SKILL.md` — fonte única de cada papel (on-demand).
- `.claude/agents/{papel}.md` — invólucro de delegação isolada, com tools restritas por papel.
- `.claude/settings.json` + `.claude/hooks/` — guardrails determinísticos: enforce Sail (`guard_sail`),
  escopo de escrita por papel (`guard_path`), formatação (`format`) e denies de comandos destrutivos.

## Comandos (via Sail)
`./vendor/bin/sail up -d` · `./vendor/bin/sail artisan test` · `./vendor/bin/sail composer lint` ·
`./vendor/bin/sail npm run types:check` · `./vendor/bin/sail composer dump-autoload`
