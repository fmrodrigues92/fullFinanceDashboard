# Spec: Dashboard V3 — Pró-labore Real + Fator R

- **Bounded Context:** Dashboard (cross-context: Companies + Invoicing) + Companies (cronjob)
- **Status:** aprovada
- **Autor:** /gerente
- **Data:** 2026-06-04

## 1. Objetivo de negócio

O card de Pró-labore do dashboard exibe dados reais:
- **Meses passados:** valor dos recibos emitidos (manual ou auto), ou "sem faturamento" se o mês não teve receita.
- **Mês corrente e futuros:** prévia baseada nas configurações, condicionada à existência de faturamento (real ou simulado).
- **Fator R:** badge informando se a empresa está dentro do Fator R (≥ 28%) para o mês selecionado, exclusivo para Simples Nacional.

Um cronjob Laravel auto-gera os recibos na madrugada do dia 1 de cada mês, para cada empresa × sócio com config, sempre que o mês anterior teve faturamento real.

## 2. Atores

- **Usuário autenticado** — vê apenas suas próprias empresas.
- **Cronjob** — ator automático do sistema; não autenticado via HTTP.

## 3. User stories

- US1: Como usuário, quero ver no card de Pró-labore os valores reais dos recibos dos meses passados, com indicação de se foram lançados manualmente ou pelo sistema.
- US2: Como usuário, quero ver uma prévia do pró-labore para o mês atual e meses futuros (baseada nas configurações), desde que haja faturamento previsto.
- US3: Como usuário, quero saber se minha empresa está dentro do Fator R (≥ 28%) para o mês selecionado, com o percentual exato e os valores usados no cálculo.
- US4: Como usuário, quero que meses sem faturamento (real ou simulado) mostrem claramente que o pró-labore não se aplica naquele período.
- US5: Como operador do sistema, quero que o cronjob gere recibos automáticos no dia 1 para todos os meses com faturamento real, sem sobrescrever recibos já lançados manualmente.

## 4. Regras de negócio

### Classificação de meses

- **RN1:** Mês fechado = todo mês anterior ao mês atual (primeiro dia do mês corrente como fronteira). Independe de ter recibo.
- **RN2:** Mês aberto = mês atual. Mês futuro = qualquer mês posterior ao atual.

### Fonte dos dados de pró-labore

- **RN3:** Mês fechado com recibo → exibir valor do recibo e sua origem (`manual` ou `automatico`).
- **RN4:** Mês fechado sem recibo + com faturamento real → estado `sem_recibo` (edge case: cronjob pendente ou recibo deletado); exibir valor da config como referência.
- **RN5:** Mês fechado sem faturamento real → estado `sem_faturamento`; pró-labore não aplicável.
- **RN6:** Mês aberto ou futuro com faturamento (real ou simulado) → estado `previsao`; valor da config.
- **RN7:** Mês aberto ou futuro sem faturamento → estado `sem_faturamento`.
- **RN8:** Empresa sem nenhuma config de pró-labore + com faturamento → estado `sem_config`.

### Cronjob de auto-geração

- **RN9:** O cronjob `GenerateProlaboreRecordsCommand` executa toda madrugada, mas age apenas quando `today == primeiro dia do mês`.
- **RN10:** Para cada empresa × sócio com `prolabore_config`, verifica se o mês anterior teve faturamento real (invoices não deletadas, `is_simulation = false`).
- **RN11:** Se havia faturamento E não existe nenhum `prolabore_record` para aquele sócio/competência → cria registro com `valor` da config e `origem = 'automatico'`.
- **RN12:** Se já existe registro (qualquer origem) → não sobrescreve.
- **RN13:** Se não havia faturamento → não cria registro.
- **RN14:** O campo `origem` em `prolabore_records` armazena `'manual'` (default, lançamentos via UI) ou `'automatico'` (criado pelo cronjob).

### Fator R

- **RN15:** Exibido apenas para empresas com `regime_tributario = 'simples_nacional'`.
- **RN16:** Fórmula: `Fator R = Folha12 / RBT12`, onde:
  - `Folha12` = soma de pró-labore dos 12 meses anteriores ao mês de apuração.
  - `RBT12` = soma de `valor_brl` de invoices reais (não deletadas, `is_simulation = false`) dos 12 meses anteriores.
- **RN17:** `dentro = true` quando `Fator R ≥ 28%` (Anexo III, regime mais favorável).
- **RN18:** Para meses futuros, `Folha12` usa config como proxy para meses sem recibo; `RBT12` usa invoices reais + simuladas (`is_simulation = true`) onde não há dado real. Badge marcado como `estimado = true`.
- **RN19:** Salários CLT **não** compõem `Folha12` nesta entrega (inexistência de entidade de folha). O percentual pode estar subestimado para empresas com empregados CLT — informação a ser adicionada em feature futura.
- **RN20:** Se `RBT12 = 0`, Fator R não é calculável → exibir `null` no campo `fator_r`.

## 5. Critérios de aceite

**Cronjob:**
- [ ] CA1: Cronjob schedulado para 01:00 do dia 1 de cada mês; ao rodar em outros dias, não cria registros.
- [ ] CA2: Cria recibo automático para competência do mês anterior quando há faturamento e não há recibo prévio.
- [ ] CA3: Não sobrescreve recibo manual já existente.
- [ ] CA4: Não cria recibo quando não há faturamento real no mês anterior.
- [ ] CA5: `origem` do recibo criado é `'automatico'`; recibos lançados via UI têm `origem = 'manual'`.

**Dashboard — pró-labore:**
- [ ] CA6: Mês passado com recibo manual → tipo `recibo_manual`, valor e badge "lançado manualmente".
- [ ] CA7: Mês passado com recibo automático → tipo `recibo_automatico`, valor e badge "gerado automaticamente".
- [ ] CA8: Mês passado com faturamento mas sem recibo → tipo `sem_recibo`, valor da config como referência.
- [ ] CA9: Mês passado ou futuro sem faturamento → tipo `sem_faturamento`, sem valor de pró-labore.
- [ ] CA10: Mês aberto/futuro com faturamento → tipo `previsao`, valor da config.
- [ ] CA11: Breakdown por sócio segue o mesmo `tipo` da competência no nível individual.

**Fator R:**
- [ ] CA12: Badge de Fator R exibido apenas para Simples Nacional; ausente para outros regimes.
- [ ] CA13: Percentual exibido com 1 casa decimal (ex: `32,4%`).
- [ ] CA14: Badge verde "Dentro do Fator R" quando `≥ 28%`; vermelho "Fora do Fator R" quando `< 28%`.
- [ ] CA15: Badge com indicador "estimado" quando calculado com dados projetados (meses futuros).
- [ ] CA16: `fator_r = null` quando `RBT12 = 0` (sem receita nos 12 meses anteriores).

**Técnico:**
- [ ] CA17: `./vendor/bin/sail artisan test` → verde.
- [ ] CA18: `./vendor/bin/sail npm run types:check` → zero erros.

## 6. Escopo

**Dentro:**
- Migration: coluna `origem` em `prolabore_records`.
- Cronjob `GenerateProlaboreRecordsCommand` + scheduling.
- `GetProlaboreDashboardUseCase` e query agregada no backend.
- `DashboardController`: novo prop `prolaborePorEmpresa`.
- `ProlaboreCard` do dashboard: dados reais + Fator R.

**Fora:**
- Salários CLT no Fator R (requer feature de folha de pagamento).
- Cálculo do DAS com base no Fator R (feature DAS real).
- Emissão ou download do PDF do recibo.
- Edição de recibos automáticos via dashboard (ação na tela de pró-labore da feature 001).

## 7. Modelo de dados

### Alteração: `prolabore_records`

```sql
ALTER TABLE prolabore_records
  ADD COLUMN origem VARCHAR(20) NOT NULL DEFAULT 'manual'
  CONSTRAINT prolabore_records_origem_check CHECK (origem IN ('manual', 'automatico'));
```

Índice recomendado (para o cronjob e para queries do dashboard):
```sql
CREATE INDEX prolabore_records_company_competencia ON prolabore_records (company_id, competencia);
```

### Leituras (sem tabelas novas)

- `prolabore_records` — recibos por empresa × sócio × competência
- `prolabore_configs` — valor configurado por empresa × sócio
- `company_partners` — nome do sócio
- `invoices` — faturamento real (`is_simulation = false`) e simulado (`is_simulation = true`)

## 8. Dependências

- Feature 001 (companies) — `prolabore_records`, `prolabore_configs`, `company_partners` já existem.
- Feature 002 (invoicing) — `invoices` com `is_simulation` já existe.
- Feature 004 (dashboard v2) — padrão de payload `xyzPorEmpresa[companyId][YYYY-MM]` já estabelecido.

## 9. Handoff

- [x] Spec aprovada
- [x] Contrato publicado em `docs/contracts/005-dashboard-v3.md`
- [ ] Pronto para `/backend` e `/frontend` em paralelo
