# SDD — Spec-Driven Development

Este projeto é construído por **especificação primeiro**: nenhuma linha de código de feature é escrita antes
de existir uma spec **e** um contrato aprovados pelo `/gerente` (tech-lead).

## Pipeline

```
  usuário
    │  requisitos
    ▼
┌──────────────┐  spec  +  contrato   ┌──────────┐
│   /gerente   │ ───────────────────▶ │ /backend │  (em paralelo)
│  tech-lead   │ docs/specs/          │ /frontend│
└──────────────┘ docs/contracts/      └─────┬────┘
                                            │ implementação back
                                            ▼
                                  ┌──────────┴──────────┐
                                  ▼                     ▼
                             ┌─────────┐           ┌──────────┐
                             │ /tester │           │ /auditor │
                             └─────────┘           └──────────┘
                              unit tests           segurança +
                              dos internals        performance
                                                   (docs/audits/)
```

## Artefatos e onde vivem

| Pasta | Dono | Conteúdo |
|-------|------|----------|
| `docs/specs/` | `/gerente` | Uma spec por feature: HU, regras de negócio, critérios de aceite, modelo de dados. **Sem contrato técnico.** |
| `docs/contracts/` | `/gerente` | Contrato definitivo (endpoints, schemas, tipos TS, erros) — fonte da verdade entre back e front. |
| `docs/progress/STATUS.md` | `/gerente` (também atualizado por `/auditor`) | Quadro de acompanhamento: em que fase cada feature está. |
| `docs/audits/` | `/auditor` | Relatório de segurança + performance por feature; correções catalogadas para o `/backend`. |
| `app/src/{Context}/` | `/backend` | Implementação DDD por bounded context. |
| `resources/js/pages/{Context}/` | `/frontend` | Páginas Inertia + TypeScript tipado pelo contrato. |

## Como usar

1. **Começar uma feature:** `/gerente` — levante requisitos **e** desenhe o contrato. Saída: `docs/specs/{feature}.md` + `docs/contracts/{feature}.md`.
2. **Implementar em paralelo:**
   - `/backend` — implementa contra o contrato. Saída: código em `app/src/`, controllers/rotas Wayfinder, feature tests.
   - `/frontend` — consome o contrato (com fixtures TS se o back ainda não publicou a rota). Saída: páginas tipadas 1:1 pelo contrato.
3. **Cobrir e auditar (em paralelo, após o `/backend`):**
   - `/tester` — unit tests do que o feature test não cobre.
   - `/auditor` — varre os arquivos da feature por segurança e performance, gera `docs/audits/{feature}-{data}.md`,
     pergunta se cataloga as correções para o `/backend` e atualiza o `STATUS.md`. Nunca edita código.

Skills são a porta de entrada (você dirige cada fase). Para trabalho pesado e isolado, cada skill pode delegar
ao subagent correspondente em `.claude/agents/`.

## Regra de ouro do contrato
O **contrato** (`docs/contracts/`) é a fronteira entre backend e frontend e tem **um único dono: o `/gerente`**.
- `/backend` e `/frontend` **leem** o contrato; **não editam**.
- Divergência (campo inviável, regra ambígua, problema de segurança/performance) → reporte ao `/gerente`, que
  edita o contrato, registra a revisão (data + motivo) e avisa ambos os lados.
- Tipos TS no frontend espelham o contrato 1:1.

**Transporte:** o app é **Inertia.js** — leituras chegam como props de página e escritas como redirect + flash;
o mesmo endpoint responde JSON sob `Accept: application/json`. O contrato descreve o shape dos dados (igual nos dois).
