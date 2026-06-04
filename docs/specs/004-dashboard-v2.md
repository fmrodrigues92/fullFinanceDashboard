# Spec: Dashboard V2 — Faturamento Real por Competência

- **Bounded Context:** Dashboard (leitura cross-context de Invoicing)
- **Status:** aprovada (post-hoc — implementada antes de documentar)
- **Autor:** /gerente
- **Data:** 2026-06-03

> Os dados reais substituem o fixture `MOCK_DASHBOARD.faturamento` no card de Faturamento do dashboard.
> DAS, Pró-labore e Gastos permanecem mockados até suas respectivas features.

## 1. Objetivo de negócio

O usuário precisa ver, no card de Faturamento do dashboard, os valores reais das notas fiscais emitidas —
total em BRL e breakdown nacional/internacional — filtrados pela empresa e competência selecionadas.

## 2. Atores

- **Usuário autenticado** — vê somente os dados das suas próprias empresas.

## 3. User stories

- US1: Como usuário, quero que o card Faturamento exiba o total real faturado na competência selecionada.
- US2: Como usuário, quero ver quantas notas reais foram emitidas naquela competência.
- US3: Como usuário, quero ver o breakdown por tipo (nacional / internacional) ao expandir o card.
- US4: Como usuário, quando não há notas em uma competência, quero ver R$ 0,00 e "0 nota(s)".

## 4. Regras de negócio

- RN1: Apenas notas **reais** contam (`is_simulation = false`); simulações são ignoradas.
- RN2: Notas excluídas (soft-delete) não aparecem.
- RN3: A janela de competências é idêntica à da feature 003: 13 meses centrados no mês atual.
- RN4: Os dados são carregados **uma única vez** no page load (prop Inertia) e a navegação entre meses fica client-side — sem requisição adicional ao servidor ao mudar competência ou empresa.
- RN5: O mapa `faturamentoPorEmpresa` cobre todas as empresas do usuário e todos os 13 meses, mesmo se vazios.
- RN6: Nota sem `deleted_at` e sem `is_simulation` → incluída no total do mês de `data_emissao`.

## 5. Critérios de aceite

- [ ] CA1: Card Faturamento exibe o total real das notas da empresa + competência selecionadas.
- [ ] CA2: Número de notas exibido bate com o COUNT real do banco.
- [ ] CA3: Breakdown expandido lista cada tipo (nacional, internacional) com valor e quantidade corretos.
- [ ] CA4: Competência sem notas exibe R$ 0,00 / 0 nota(s) / sem linhas no breakdown.
- [ ] CA5: Trocar empresa ou competência atualiza o card sem recarregar a página.
- [ ] CA6: Notas simuladas e notas deletadas não aparecem nos totais.
- [ ] CA7: `./vendor/bin/sail npm run types:check` → zero erros.
- [ ] CA8: `./vendor/bin/sail artisan test --filter=DashboardTest` → verde.

## 6. Escopo

**Dentro:**
- Agregação de faturamento real (total + breakdown por tipo) no DashboardController.
- Novo método `faturamentoPorCompetencias` no `InvoiceRepository`.
- Novo use case `GetFaturamentoDashboardUseCase`.
- Frontend: substituição do fixture por dado real no `FaturamentoCard`.

**Fora:**
- DAS real (feature futura — requer RBT12).
- Pró-labore real (feature futura — requer `prolabore_records`).
- Gastos reais (feature futura — requer `company_expenses`).
- Notas em moeda estrangeira convertidas para BRL (campo `valor_brl` já armazena o valor convertido).

## 7. Modelo de dados

Nenhuma tabela nova. Leitura da tabela `invoices` já existente (feature 002):

```
invoices
├── company_id      FK → companies.id
├── is_simulation   bool
├── tipo            enum('nacional','internacional')
├── data_emissao    date
├── valor_brl       decimal
└── deleted_at      timestamp (soft delete)
```

Query: `GROUP BY TO_CHAR(data_emissao, 'YYYY-MM'), tipo` filtrado por `company_id`, `is_simulation = false`, `deleted_at IS NULL`.

## 8. Dependências

- Feature 001 (companies) — `ListCompaniesUseCase` já existe.
- Feature 002 (invoicing) — tabela `invoices` e `EloquentInvoiceRepository` já existem.
- Feature 003 (dashboard mocado) — layout e componentes do dashboard já existem.

## 9. Handoff

- [x] Spec aprovada
- [x] Contrato publicado em `docs/contracts/004-dashboard-v2.md`
- [x] Implementação concluída (backend + frontend em paralelo, sessão única)
- [x] `STATUS.md` atualizado
