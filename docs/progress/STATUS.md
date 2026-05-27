# Status do Projeto — fullFinanceDashboard

Quadro de acompanhamento a nível de negócio. Mantido pelo `/gerente`.
Última atualização: 2026-05-25

## Legenda de fases
`spec` → `backend` → `tester` → `frontend` → `done`

## Features

| Feature | Context | Fase | Spec | Contrato | Notas |
|---------|---------|------|------|----------|-------|
| 001 — Empresa, Sócios e Pró-labore | Companies | `done` | `docs/specs/001-companies.md` | `docs/contracts/001-companies.md` | Entregue 2026-05-26; 72 testes passando; UI validada pelo usuário |

## Backlog
- Feature de fechamento de mês (consumirá `prolabore_records` da feature 001)
- Vínculo de empresa com transações financeiras

## Decisões de negócio
- 2026-05-25 — Sócios são dados cadastrais (nome, CPF, % participação), não usuários do sistema. Simplifica isolamento e escopo desta entrega.
- 2026-05-25 — Soma de participações deve fechar 100% ao sincronizar o quadro societário; empresa pode existir sem sócios.
- 2026-05-25 — Pró-labore tem dois níveis: config (valor mensal por sócio) e recibos (registro por competência). Sem cálculo automático ou regra de fechamento nesta entrega.
- 2026-05-25 — `competencia` em `prolabore_records` armazenada como `date` (dia 01 do mês) para facilitar queries por período.
