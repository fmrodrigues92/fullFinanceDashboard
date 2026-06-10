# Spec: Criar/Editar Recibo de Pró-labore Inline no Dashboard

- **Bounded Context:** Dashboard (UI) + Companies (escrita de `prolabore_records`)
- **Status:** aprovada
- **Autor:** /gerente
- **Data:** 2026-06-04

> Esta spec descreve **o quê e por quê** (negócio). A fronteira técnica (endpoints, schemas, tipos)
> vive em `docs/contracts/006-prolabore-dashboard-inline.md`.

## 1. Objetivo de negócio

No card de Pró-labore do **mês corrente**, o usuário lança ou edita o recibo de pró-labore de cada sócio
direto no dashboard, sem navegar até a tela de pró-labore da empresa. Reduz o atrito do fechamento mensal:
o valor já vem pré-preenchido (recibo existente ou prévia da config), o usuário só confirma ou ajusta em R$.

## 2. Atores

- **Usuário autenticado** — lança/edita recibos apenas das próprias empresas (isolamento por `user_id`/`company_id`).

## 3. User stories

- US1: Como usuário, quero lançar o pró-labore de um sócio no mês atual direto no card do dashboard,
  com o valor da prévia já preenchido, para confirmar o fechamento sem trocar de tela.
- US2: Como usuário, quero editar um recibo já lançado no mês atual (manual ou automático) ajustando o valor
  em R$, para corrigir lançamentos sem ir à tela de pró-labore.
- US3: Como usuário, quero que o card atualize após salvar (novo valor, novo `tipo`, Fator R recalculado)
  sem recarregar a página inteira.

## 4. Regras de negócio

### Escopo da interação

- **RN1:** A edição inline aparece **apenas no mês corrente** (competência = primeiro dia do mês atual).
  Meses passados e futuros permanecem somente-leitura no dashboard.
- **RN2:** A interação existe por **sócio**: cada sócio da empresa selecionada tem seu próprio controle de
  lançamento dentro do card expandido. Empresas sem sócios/configs não exibem controle.

### Pré-preenchimento do valor

- **RN3:** Se **já existe** `prolabore_record` para o sócio na competência atual → o input carrega o
  `valor` do recibo existente; a ação é **editar** (PUT).
- **RN4:** Se **não existe** recibo → o input carrega o valor da **prévia da config** já convertido para BRL:
  - config `tipo = 'fixo'` → o próprio `valor`;
  - config `tipo = 'percentual'` → `round(faturamento_mês × (config.valor / 100), 2)`.
  A ação é **criar** (POST).
- **RN5:** Sócio sem config e sem recibo → não há valor de prévia; o input inicia vazio e o usuário digita o valor.
- **RN6:** O usuário **sempre vê e edita em R$**. Percentuais nunca aparecem no input; são apenas a origem do número pré-preenchido.

### Persistência

- **RN7:** Recibos **criados** por este fluxo têm `origem = 'manual'`.
- **RN8:** Ao **editar** um recibo existente via dashboard, `origem` passa a `'manual'` — o usuário assume o
  lançamento, mesmo que o recibo tenha sido gerado pelo cronjob (`'automatico'`). Decisão de negócio:
  edição humana sempre marca o recibo como manual.
- **RN9:** `valor` deve ser numérico e **> 0**. Valor zero/negativo é inválido (para "zerar" um pró-labore o
  usuário usa a tela de pró-labore da empresa para excluir o recibo — exclusão está fora desta entrega).
- **RN10:** `observacao` é opcional (texto curto, máx. 500 caracteres).

### Restrições de segurança e integridade

- **RN11:** A `company_id` alvo deve pertencer ao usuário autenticado (Policy). Caso contrário, `403`.
- **RN12:** O `partner_id` deve pertencer à `company_id` alvo. Caso contrário, `422`.
- **RN13:** A `competencia` enviada deve ser **o mês corrente**. Competência passada ou futura por este fluxo → `422`.
  (A tela de pró-labore da empresa continua permitindo qualquer competência; a restrição é exclusiva do dashboard.)
- **RN14:** Não é possível criar duplicata `(company_id, partner_id, competencia)` — a unique constraint já existe;
  a validação retorna `422` com mensagem amigável. Quando há recibo, o fluxo correto é editar (RN3), não criar.

### Atualização da view

- **RN15:** Após salvar (criar ou editar), o dashboard recarrega apenas os dados de pró-labore da empresa
  (sem reload completo da página). O card reflete novo valor, novo `tipo` do sócio (`recibo_manual`) e Fator R recalculado.

## 5. Critérios de aceite

- [ ] CA1: No mês corrente, o card de pró-labore expandido mostra, por sócio, um controle de lançamento com input em R$.
- [ ] CA2: Meses passados e futuros não exibem o controle de lançamento (somente-leitura).
- [ ] CA3: Sócio com recibo existente → input pré-preenchido com o valor do recibo; salvar dispara edição (PUT).
- [ ] CA4: Sócio sem recibo mas com config → input pré-preenchido com a prévia em R$; salvar dispara criação (POST).
- [ ] CA5: Sócio sem recibo e sem config → input vazio; salvar com valor válido dispara criação (POST).
- [ ] CA6: Recibo criado pelo dashboard tem `origem = 'manual'`.
- [ ] CA7: Editar recibo `automatico` pelo dashboard resulta em `origem = 'manual'`.
- [ ] CA8: Valor ≤ 0 → erro de validação exibido no card, sem requisição persistida.
- [ ] CA9: Tentar lançar para empresa de outro usuário → `403`.
- [ ] CA10: `partner_id` que não pertence à empresa → `422`.
- [ ] CA11: Competência diferente do mês atual → `422`.
- [ ] CA12: Criar quando já existe recibo do sócio na competência → `422` com mensagem amigável.
- [ ] CA13: Após salvar, o card atualiza valor, badge de tipo do sócio e Fator R sem reload completo.
- [ ] CA14: Estados de loading (durante a requisição) e de erro (validação/servidor) são exibidos no card.
- [ ] CA15: `./vendor/bin/sail artisan test` → verde.
- [ ] CA16: `./vendor/bin/sail npm run types:check` → zero erros.

## 6. Escopo

**Dentro:**
- Reuso/ajuste dos endpoints existentes de `prolabore_records` (store/update) para o fluxo do dashboard.
- Restrição de competência ao mês corrente neste fluxo (RN13) e redirect compatível com a permanência no dashboard.
- Marcação `origem = 'manual'` ao editar via dashboard (RN8).
- Exposição de `partner_id` e `record_id` por sócio no payload do dashboard (delta sobre o contrato 005).
- Controle inline por sócio no `ProlaboreCard` do mês corrente: input em R$, observação opcional, salvar,
  loading, erro, recarregamento parcial.

**Fora (nesta entrega):**
- Exclusão de recibo via dashboard (continua na tela de pró-labore da empresa).
- Edição inline em meses passados/futuros.
- Lançamento de pró-labore para múltiplos sócios numa única requisição (cada sócio é um POST/PUT independente).
- Emissão/download de PDF do recibo.

## 7. Modelo de dados

Sem novas tabelas nem migrations. Reuso de `prolabore_records` (feature 001/005):
`company_id`, `partner_id`, `user_id`, `competencia` (date, dia 01), `valor` (float), `observacao`,
`origem` ('manual' | 'automatico'). Unique `(company_id, partner_id, competencia)` já existe.

O delta desta feature é de **leitura**: o payload do dashboard passa a expor `partner_id` e `record_id`
por sócio, para o frontend mapear o controle de lançamento e decidir POST vs PUT. Ver contrato.

## 8. Dependências e riscos

- Feature 001 — `prolabore_records`, `prolabore_configs`, `company_partners`, e os UseCases/Requests de
  store/update de recibo já existem (`ProlaboreRecordController`).
- Feature 005 — `GetProlaboreDashboardUseCase` produz o shape de `prolaborePorEmpresa`; já calcula `valor` por sócio.
- **Risco (redirect):** o `store` atual redireciona para `companies.prolabore-records.index`. No fluxo do
  dashboard isso tiraria o usuário da tela. O contrato define o redirect/flash compatível com a permanência
  no dashboard (ex.: `redirect()->back()`), preservando o comportamento da tela de pró-labore quando aplicável.
- **Risco (record_id):** o contrato 005 **não** expõe `partner_id`/`record_id` nos `socios`. Sem isso o
  frontend não sabe a qual sócio o input pertence nem se deve criar ou editar — esta feature adiciona ambos.

## 9. Handoff

- [x] Spec aprovada
- [x] Contrato publicado em `docs/contracts/006-prolabore-dashboard-inline.md`
- [x] `docs/progress/STATUS.md` atualizado
- [ ] Pronta para `/backend` e `/frontend` (paralelo)
