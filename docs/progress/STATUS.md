# Status do Projeto — fullFinanceDashboard

Quadro de acompanhamento a nível de negócio. Mantido pelo `/gerente`.
Última atualização: 2026-06-04

## Legenda de fases
`spec` → `backend` → `tester` → `frontend` → `done`

## Features

| Feature | Context | Fase | Spec | Contrato | Notas |
|---------|---------|------|------|----------|-------|
| 001 — Empresa, Sócios e Pró-labore | Companies | `done` | `docs/specs/001-companies.md` | `docs/contracts/001-companies.md` | Entregue 2026-05-26; 72 testes passando; UI validada pelo usuário |
| 002 — Cadastro de Faturamento | Invoicing | `done` | `docs/specs/002-invoicing.md` | `docs/contracts/002-invoicing.md` | Backend entregue 2026-05-27; 28 feature tests passando (139 total); frontend completo 2026-05-27 — Clientes, Notas Fiscais, Lotes de Simulação; types:check zero erros |
| 003 — Dashboard Mocado | Dashboard | `done` | `docs/specs/003-dashboard.md` | `docs/contracts/003-dashboard.md` | Frontend entregue 2026-05-29; DashboardController criado; 13 cards de competência, 4 seções colapsáveis (DAS, Pró-labore, Faturamento, Gastos); types:check zero erros |
| 004 — Dashboard V2: Faturamento Real | Dashboard | `auditoria-pendente` | `docs/specs/004-dashboard-v2.md` | `docs/contracts/004-dashboard-v2.md` | Backend + frontend entregues 2026-06-03; 82 testes verdes; auditoria: 2 achados BAIXO — SEC-01 (ownership check no repository) e PERF-01 (N+1 → consolidar em query única); correções em `docs/audits/004-dashboard-v2-2026-06-03.md` |

| 005 — Dashboard V3: Pró-labore Real + Fator R | Dashboard + Companies | `auditoria-ok` | `docs/specs/005-dashboard-v3.md` | `docs/contracts/005-dashboard-v3.md` | Backend + frontend entregues 2026-06-04; migração origem em prolabore_records; GetProlaboreDashboardUseCase; GenerateProlaboreRecordsCommand schedulado; ProlaboreCard redesenhado com badge de tipo e Fator R; 44 testes verdes. Auditoria: SEC-01 (MÉDIO) e SEC-02 (BAIXO) corrigidos em 2026-06-04. Ver `docs/audits/005-dashboard-v3-2026-06-04.md` |

## Backlog
- Feature de fechamento de mês (consumirá `prolabore_records` da feature 001)
- Feature de DAS real (conectará ao dashboard da feature 003; requer cálculo RBT12)
- Feature de gastos da empresa (conectará ao dashboard da feature 003; nova entidade `company_expenses`)
- Vínculo de empresa com transações financeiras

## Decisões de negócio
- 2026-05-25 — Sócios são dados cadastrais (nome, CPF, % participação), não usuários do sistema. Simplifica isolamento e escopo desta entrega.
- 2026-05-25 — Soma de participações deve fechar 100% ao sincronizar o quadro societário; empresa pode existir sem sócios.
- 2026-05-25 — Pró-labore tem dois níveis: config (valor mensal por sócio) e recibos (registro por competência). Sem cálculo automático ou regra de fechamento nesta entrega.
- 2026-05-25 — `competencia` em `prolabore_records` armazenada como `date` (dia 01 do mês) para facilitar queries por período.
