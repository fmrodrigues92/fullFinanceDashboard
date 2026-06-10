# Spec: Dashboard Financeiro por Empresa (Mocado)

- **Bounded Context:** Dashboard (visualização cross-context; sem lógica de domínio própria)
- **Status:** aprovada
- **Autor:** /gerente
- **Data:** 2026-05-29

> Esta spec descreve **o quê e por quê** (negócio). A fronteira técnica vive em `docs/contracts/003-dashboard.md`.

## 1. Objetivo de negócio

O usuário precisa ver, em um único lugar, um panorama financeiro de cada empresa que administra para uma
competência específica: DAS a pagar, pró-labore, faturamento e gastos da empresa. Como os cálculos reais
(DAS, gastos) ainda não têm backend, esta entrega estabelece o **layout final do dashboard** com dados
fictícios, permitindo validar UX e fluxo de navegação antes de conectar os dados reais em features futuras.

## 2. Atores

- **Usuário autenticado** — acessa apenas suas próprias empresas (isolamento via `user_id`).
- Não há atores externos. Sócios e clientes são dados cadastrais, não usuários.

## 3. User stories

- US1: Como usuário, quero selecionar uma das minhas empresas no dashboard para ver seu panorama financeiro.
- US2: Como usuário, quero navegar entre competências clicando em cards de mês para visualizar períodos diferentes.
- US3: Como usuário, quero ver o DAS da competência selecionada com valor, vencimento e status de pagamento.
- US4: Como usuário, quero ver o pró-labore da competência com o total e o detalhamento por sócio.
- US5: Como usuário, quero ver o faturamento da competência com total e breakdown por tipo de nota.
- US6: Como usuário, quero ver os gastos da empresa (INSS, cartão, outras taxas) com total e detalhes.
- US7: Como usuário, quero expandir qualquer seção para ver o detalhamento sem sair do dashboard.

## 4. Regras de negócio

- RN1: Empresa exibida por padrão = primeira da lista do usuário (ordem alfabética por `razao_social`).
- RN2: Competência exibida por padrão = mês atual (ex.: Mai/2026 em 2026-05-29).
- RN3: A faixa de navegação exibe **13 cards** de mês na horizontal: 6 meses anteriores ao atual + mês atual + 6 meses posteriores. O intervalo é calculado a partir do mês atual no momento do carregamento da página. Não há navegação além desse intervalo nesta entrega.
- RN4: Clicar em um card de mês o seleciona; todos os cards de dados atualizam para refletir os dados daquela competência (client-side, sem requisição ao servidor).
- RN5: Todos os valores exibidos são **fictícios** nesta entrega. A página exibe marcação visual clara de "dados simulados" para não confundir o usuário com dados reais.
- RN6: Cada seção (DAS, Pró-labore, Faturamento, Gastos) possui um toggle de detalhes que expande/colapsa o conteúdo inline, sem modal e sem navegação para outra página.
- RN7: O estado de expansão de cada seção é independente — abrir uma não fecha as outras.
- RN8: Gastos incluem três categorias fixas (mock): INSS empregador, cartão corporativo, outras taxas.
- RN9: Trocar a empresa no seletor atualiza todos os cards imediatamente (client-side).

## 5. Critérios de aceite

- [ ] CA1: O seletor de empresa lista todas as empresas do usuário autenticado (dados reais da API).
- [ ] CA2: Trocar empresa no seletor atualiza todos os cards de dados instantaneamente sem recarregar a página.
- [ ] CA3: A faixa de competências exibe exatamente 13 cards de mês.
- [ ] CA4: O card do mês atual está selecionado e destacado visualmente ao carregar a página.
- [ ] CA5: Clicar em outro card de mês o seleciona, desseleciona o anterior e atualiza os dados exibidos.
- [ ] CA6: Card DAS exibe: valor total formatado em BRL, data de vencimento e badge de status (pendente/pago).
- [ ] CA7: Card DAS expandido exibe lista de detalhes por linha com descrição e valor (mock).
- [ ] CA8: Card Pró-labore exibe: total e lista de sócios com nome, valor e status (mock).
- [ ] CA9: Card Pró-labore expandido exibe detalhes de cada sócio.
- [ ] CA10: Card Faturamento exibe: total em BRL e número de notas emitidas (mock).
- [ ] CA11: Card Faturamento expandido exibe breakdown: nacional e internacional, com valor e quantidade.
- [ ] CA12: Card Gastos exibe: total e lista de categorias (INSS, cartão, outras taxas) com valores (mock).
- [ ] CA13: Card Gastos expandido exibe cada item com categoria, descrição e valor.
- [ ] CA14: A página exibe indicação visível de "dados simulados" (badge, nota ou faixa informativa).
- [ ] CA15: `./vendor/bin/sail npm run types:check` → zero erros TypeScript.

## 6. Escopo

**Dentro:**
- Página `/dashboard` com seletor de empresa e navegação por competência (13 cards).
- Quatro seções mockadas: DAS, Pró-labore, Faturamento, Gastos.
- Expand/collapse inline por seção.
- Passagem da lista real de empresas via prop Inertia.

**Fora (nesta entrega):**
- Cálculo real de DAS (requer RBT12 — feature futura).
- Cadastro ou persistência de gastos (feature futura).
- Leitura de `prolabore_records` reais via API (feature futura).
- Leitura de `invoices` reais via API (feature futura).
- Filtros adicionais além de empresa e competência.
- Exportação ou impressão do dashboard.

## 7. Modelo de dados proposto

Nenhuma tabela nova nesta entrega. Os dados exibidos são fixtures client-side.

Dependências de leitura (sem novos endpoints):
- `companies` (Feature 001) — lista passada como prop Inertia.

Shape dos fixtures (definido em `resources/js/fixtures/dashboard.ts`):
```
DashboardMockData
├── das: { valor, vencimento, status, detalhes[] }
├── prolabore: { total, socios[]: { nome, valor, status } }
├── faturamento: { total, notas_emitidas, itens[]: { tipo, valor, quantidade } }
└── gastos: { total, itens[]: { categoria, descricao, valor } }
```

## 8. Dependências e riscos

- **Depende de:** Feature 001 (companies) — rota e UseCase de listagem já existem.
- **Risco:** Dados simulados podem criar expectativa errada no usuário; mitigado pelo badge "dados simulados".
- **Risco:** Futuras features de DAS e gastos precisarão substituir os fixtures por chamadas reais — o shape dos tipos TS desta feature deve ser mantido compatível.

## 9. Handoff

- [x] Spec aprovada pelo operador
- [x] Contrato publicado em `docs/contracts/003-dashboard.md`
- [x] `docs/progress/STATUS.md` atualizado
- [ ] Pronta para `/frontend` (feature 100% frontend; sem `/backend` necessário além de converter a rota do dashboard)
