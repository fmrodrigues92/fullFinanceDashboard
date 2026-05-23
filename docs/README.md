# SDD — Spec-Driven Development

Este projeto é construído por **especificação primeiro**: nenhuma linha de código de feature é escrita antes
de existir uma spec aprovada, e o frontend nunca começa antes de existir um contrato publicado pelo backend.

## Pipeline

```
  usuário
    │  requisitos
    ▼
┌─────────┐  spec        ┌─────────┐  contrato     ┌──────────┐
│ /gerente│ ───────────▶ │ /backend│ ────────────▶ │ /frontend│
└─────────┘ docs/specs/  └─────────┘ docs/contracts└──────────┘
                              │
                              │ implementação
                              ▼
                         ┌─────────┐
                         │ /tester │  unit tests dos internals
                         └─────────┘
```

## Artefatos e onde vivem

| Pasta | Dono | Conteúdo |
|-------|------|----------|
| `docs/specs/` | `/gerente` | Uma spec por feature: requisitos de negócio, regras, critérios de aceite, contrato preliminar. |
| `docs/contracts/` | `/backend` | Contrato de API definitivo (endpoints, schemas, tipos TS, erros) — fonte da verdade para o frontend. |
| `docs/progress/STATUS.md` | `/gerente` | Quadro de acompanhamento de negócio: em que fase cada feature está. |
| `app/src/{Context}/` | `/backend` | Implementação DDD por bounded context. |
| `resources/js/pages/{Context}/` | `/frontend` | Páginas Inertia + TypeScript. |

## Como usar

1. **Começar uma feature:** `/gerente` — faça o levantamento de requisitos. Saída: `docs/specs/{feature}.md`.
2. **Implementar backend:** `/backend` — passe a spec. Saída: código em `app/src/`, feature tests e `docs/contracts/{feature}.md`.
3. **Cobrir internals:** `/tester` — unit tests do que o feature test não cobre.
4. **Construir UI:** `/frontend` — passe o contrato. Saída: páginas tipadas pelo contrato.

Skills são a porta de entrada (você dirige cada fase). Para trabalho pesado e isolado, cada skill pode delegar
ao subagent correspondente em `.claude/agents/`.

## Regra de ouro do contrato
O **contrato** (`docs/contracts/`) é a fronteira entre backend e frontend. Se o backend mudar o contrato,
atualiza o arquivo; o frontend só confia no que está escrito ali. Tipos TS no frontend devem refletir o contrato 1:1.
