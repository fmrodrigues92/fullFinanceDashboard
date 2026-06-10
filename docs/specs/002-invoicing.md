# Spec: Cadastro de Faturamento (Notas e Simulações)

- **Bounded Context:** `Invoicing`
- **Status:** rascunho
- **Autor:** /gerente
- **Data:** 2026-05-26

## 1. Objetivo de negócio

O usuário registra as notas fiscais de prestação de serviços emitidas por suas empresas (Simples Nacional, Anexo 3 ou 5), identificando o cliente, o valor em BRL e, quando internacional, também em USD com a cotação. Pode cadastrar simulações de faturamento futuro em lote para planejar receita — simulações e notas reais são a mesma entidade e são exibidas e somadas juntas.

## 2. Atores

- **Usuário autenticado** — registra notas e simulações para as empresas que administra.
- Todo isolamento é via `company_id → companies.user_id`; nenhuma tabela desta feature tem `user_id` próprio.
- **Clientes** são registros cadastrais por empresa (nome + extID), não usuários do sistema.

## 3. User stories

- Como usuário, quero cadastrar um cliente com nome e identificador externo para vinculá-lo às notas da empresa.
- Como usuário, quero registrar uma nota fiscal nacional informando cliente, valor BRL, anexo CNAE e data de emissão.
- Como usuário, quero registrar uma nota fiscal internacional informando adicionalmente valor USD e cotação BRL/USD.
- Como usuário, quero cadastrar em lote simulações de faturamento futuro informando tipo, valor BRL, intervalo de datas e anexo CNAE.
- Como usuário, quero visualizar notas reais e simuladas juntas para avaliar o faturamento do período.
- Como usuário, quero excluir um lote de simulações de uma vez, sem precisar remover uma a uma.

## 4. Regras de negócio

### Clientes
- **RN1:** Cliente pertence a uma empresa (`company_id`); sem `user_id` próprio.
- **RN2:** `ext_id` é referência livre (texto), sem validação de formato e sem unicidade obrigatória.

### Notas fiscais reais
- **RN3:** `client_id` é obrigatório para nota real.
- **RN4:** `tipo = 'internacional'` exige `valor_usd` e `cotacao` (BRL/USD na data de emissão, informada manualmente); para `nacional` esses campos devem ser nulos.
- **RN5:** `anexo_cnae` deve ser 3 ou 5.
- **RN6:** Exclusão de nota real usa soft delete (`deleted_at`).
- **RN7:** Exclusão de cliente coloca `client_id` a NULL nas notas (set null — não em cascata).

### Simulações
- **RN8:** Simulação não tem `client_id` (nulo).
- **RN9:** O lote (`simulation_batch`) é apenas um agrupador; os dados de cada simulação (tipo, anexo_cnae, valor_brl, data_emissao) ficam na tabela `invoices`.
- **RN10:** Ao criar um lote, o sistema gera uma simulação por competência (mês) no intervalo `[data_inicio, data_termino]`; a `data_emissao` de cada simulação é o primeiro dia do mês correspondente.
- **RN11:** Máximo de 1 simulação por `(company_id, tipo, data_emissao)` — restrição de unicidade parcial (WHERE is_simulation = true). Lote rejeitado (422) se qualquer mês do intervalo já tiver simulação do mesmo tipo para a empresa.
- **RN12:** Simulações não têm soft delete. A única forma de removê-las é excluir o lote; a exclusão do lote hard-deleta em cascata todas as suas simulações (ON DELETE CASCADE no FK `simulation_batch_id`).
- **RN13:** Não existe endpoint de create/update/delete individual de simulação.

### Listagem e cálculos
- **RN14:** A listagem de invoices retorna notas reais e simuladas juntas por padrão; filtros `?is_simulation=true/false` permitem separar.
- **RN15:** Totais por competência somam valor_brl de reais e simuladas — base para cálculo de RBT12 em feature futura.

### Isolamento e autorização
- **RN16:** Todas as tabelas desta feature são isoladas via `company_id → companies.user_id`. Policy autoriza contra a empresa (CompanyPolicy para todas as operações de clients, invoices e simulation_batches).

## 5. Critérios de aceite

### Clientes
- [ ] CA1: Usuário cria cliente com nome e recebe `id` gerado.
- [ ] CA2: Listagem retorna apenas clientes da empresa solicitada.
- [ ] CA3: Usuário de outra empresa não acessa clientes (403).
- [ ] CA4: Exclusão de cliente soft-deleta o registro e põe `client_id = NULL` nas notas vinculadas.

### Notas fiscais reais
- [ ] CA5: Cria nota nacional com `client_id`, `valor_brl`, `anexo_cnae` e `data_emissao` → 201 com `id`.
- [ ] CA6: Cria nota internacional com `valor_usd` e `cotacao` → 201.
- [ ] CA7: Nota `tipo='internacional'` sem `valor_usd` → 422.
- [ ] CA8: Nota real sem `client_id` → 422.
- [ ] CA9: `anexo_cnae` fora de {3, 5} → 422.
- [ ] CA10: Acesso a nota de empresa de outro usuário → 403.
- [ ] CA11: Exclusão de nota real usa soft delete; nota não aparece na listagem após exclusão.

### Simulações
- [ ] CA12: POST no lote com `tipo`, `anexo_cnae`, `data_inicio`, `data_termino`, `valor_brl` cria uma simulação por mês no intervalo.
- [ ] CA13: Lote com pelo menos um mês conflitante para o mesmo tipo → 422 indicando os meses conflitantes.
- [ ] CA14: DELETE no lote remove o batch e hard-deleta todas as suas simulações.
- [ ] CA15: Após criação do lote, GET `/invoices` retorna as simulações junto com as notas reais.

### Listagem combinada
- [ ] CA16: GET `/invoices` sem filtro retorna reais não deletadas + simulações.
- [ ] CA17: `?is_simulation=true` retorna apenas simulações; `?is_simulation=false` retorna apenas notas reais.
- [ ] CA18: `?competencia=YYYY-MM` filtra por mês de `data_emissao`.

## 6. Escopo

**Dentro:**
- CRUD de clientes por empresa.
- CRUD de notas fiscais reais (nacional e internacional).
- Criação e exclusão em lote de simulações.
- Listagem combinada (real + simulado) com filtros.
- Cache Redis das simulações por empresa.

**Fora (nesta entrega):**
- Cálculo de DAS, alíquota efetiva, Fator R, RBT12 (→ feature "fechamento de mês").
- Integração com NFS-e / NFe eletrônica.
- Geração de PDF de nota.
- Cotação automática BRL/USD (usuário informa manualmente).
- Múltiplas moedas além de BRL/USD.
- Histórico de alterações de nota.
- Atualização individual de simulação (apenas exclusão em lote).

## 7. Modelo de dados proposto

```
clients
─────────────────────────────────────────
id           bigint PK
company_id   bigint FK → companies   cascade delete
nome         varchar(255)            not null
ext_id       varchar(100)            nullable
created_at   timestamp
updated_at   timestamp
deleted_at   timestamp               nullable (soft delete)


simulation_batches                         -- agrupador de simulações
─────────────────────────────────────────
id           bigint PK
company_id   bigint FK → companies   cascade delete
created_at   timestamp
updated_at   timestamp


invoices                                   -- notas reais + simulações
─────────────────────────────────────────
id                    bigint PK
company_id            bigint FK → companies           cascade delete
client_id             bigint FK → clients             nullable, set null on delete
simulation_batch_id   bigint FK → simulation_batches  nullable, ON DELETE CASCADE
is_simulation         boolean          not null, default false
tipo                  enum('nacional','internacional') not null
anexo_cnae            tinyint          not null        -- 3 ou 5
data_emissao          date             not null
valor_brl             decimal(10,2)    not null
valor_usd             decimal(10,2)    nullable        -- apenas internacional
cotacao               decimal(10,4)    nullable        -- BRL/USD na data; apenas internacional
observacao            text             nullable
created_at            timestamp
updated_at            timestamp
deleted_at            timestamp        nullable        -- soft delete apenas para notas reais

INDEX (company_id, data_emissao)
UNIQUE (company_id, tipo, data_emissao) WHERE is_simulation = true
```

> Simulações: `data_emissao` é sempre o dia 01 do mês da competência. A unicidade parcial garante 1 simulação por tipo por competência por empresa.

## 8. Contrato preliminar (rascunho)

### Clientes
| Método | Rota | Descrição | Policy |
|--------|------|-----------|--------|
| `GET` | `/companies/{company}/clients` | Lista clientes da empresa | `CompanyPolicy@view` |
| `POST` | `/companies/{company}/clients` | Cria cliente | `CompanyPolicy@update` |
| `PUT` | `/companies/{company}/clients/{client}` | Atualiza cliente | `ClientPolicy@update` |
| `DELETE` | `/companies/{company}/clients/{client}` | Remove cliente (soft delete) | `ClientPolicy@delete` |

### Notas fiscais
| Método | Rota | Descrição | Policy |
|--------|------|-----------|--------|
| `GET` | `/companies/{company}/invoices` | Lista notas + simulações (filtros: `competencia`, `is_simulation`, `tipo`) | `CompanyPolicy@view` |
| `POST` | `/companies/{company}/invoices` | Cria nota real | `CompanyPolicy@update` |
| `PUT` | `/companies/{company}/invoices/{invoice}` | Atualiza nota real | `InvoicePolicy@update` |
| `DELETE` | `/companies/{company}/invoices/{invoice}` | Remove nota real (soft delete) | `InvoicePolicy@delete` |

### Lotes de simulação
| Método | Rota | Descrição | Policy |
|--------|------|-----------|--------|
| `GET` | `/companies/{company}/simulation-batches` | Lista lotes da empresa | `CompanyPolicy@view` |
| `POST` | `/companies/{company}/simulation-batches` | Cria lote + simulações no intervalo | `CompanyPolicy@update` |
| `DELETE` | `/companies/{company}/simulation-batches/{batch}` | Remove lote + hard-delete das simulações | `SimulationBatchPolicy@delete` |

> POST do lote recebe: `tipo`, `anexo_cnae`, `data_inicio` (YYYY-MM), `data_termino` (YYYY-MM), `valor_brl`.

## 9. Dependências e riscos

- **Dependência:** Feature 001 (`companies`) deve existir — `company_id` é FK obrigatória.
- **Dependência futura:** Feature "fechamento de mês" usará `invoices` como base para RBT12 e cálculo de DAS.
- **Redis:** Cache de simulações por empresa (`invoicing:simulations:{company_id}`); invalidado em create/delete do lote. Não é fonte da verdade — só acelera leitura.
- **Risco:** Partial unique index (`WHERE is_simulation = true`) é PostgreSQL-específico; sem suporte nativo em outras engines. Não é problema neste stack.
- **Risco:** Cotação BRL/USD manual — sem validação de razoabilidade nesta entrega.

## 10. Handoff

- [ ] Spec aprovada pelo usuário
- [ ] `docs/progress/STATUS.md` atualizado
- [ ] Pronta para `/backend`
