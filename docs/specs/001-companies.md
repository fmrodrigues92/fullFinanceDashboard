# Spec: Cadastro de Empresa, Sócios e Pró-labore

- **Bounded Context:** `Companies`
- **Status:** aprovada
- **Autor:** /gerente
- **Data:** 2026-05-25

## 1. Objetivo de negócio

O usuário registra as empresas que administra ou possui participação, associa os sócios com seus percentuais e configura o pró-labore de cada um. Quando desejar, registra os recibos de pró-labore de uma competência — base para um fechamento de mês que será implementado em feature futura. Tudo serve de âncora para o dashboard financeiro por entidade jurídica.

## 2. Atores

- **Usuário autenticado** — cria e gerencia suas próprias empresas. Todo dado é isolado por `user_id`.
- Sócios **não** são usuários do sistema; são registros cadastrais vinculados à empresa.

## 3. User stories

- Como usuário, quero cadastrar uma empresa (CNPJ, razão social, nome fantasia, regime tributário) para identificá-la no dashboard.
- Como usuário, quero adicionar, editar e remover sócios de uma empresa informando nome, CPF e percentual de participação.
- Como usuário, quero garantir que o quadro societário some exatamente 100% antes de salvar.
- Como usuário, quero configurar o valor mensal de pró-labore de cada sócio por empresa.
- Como usuário, quero registrar um recibo de pró-labore por sócio e competência (mês/ano) para manter o histórico de pagamentos.
- Como usuário, quero listar e editar os recibos de pró-labore por empresa e período.

## 4. Regras de negócio

### Empresa e sócios
- **RN1:** CNPJ deve ser único por `user_id` (mesmo CNPJ pode existir para usuários distintos).
- **RN2:** CNPJ deve ser válido pelo algoritmo de dígitos verificadores.
- **RN3:** CPF do sócio deve ser válido pelo algoritmo de dígitos verificadores.
- **RN4:** Ao sincronizar o quadro societário, a soma dos percentuais deve ser exatamente 100 (2 casas decimais). O sistema rejeita a operação indicando a soma atual.
- **RN5:** Empresa pode ser salva sem sócios; a validação de 100% aplica-se apenas quando há pelo menos 1 sócio.
- **RN6:** Percentual de cada sócio deve ser > 0 e ≤ 100.
- **RN7:** Exclusão de empresa remove em cascata sócios, configs e recibos de pró-labore.
- **RN8:** CPF não pode ser duplicado dentro da mesma empresa.

### Configuração de pró-labore
- **RN9:** Só pode haver uma config de pró-labore por sócio por empresa (`UNIQUE partner_id + company_id`).
- **RN10:** Apenas sócios vinculados à empresa podem ter config de pró-labore nessa empresa.
- **RN11:** Exclusão de sócio remove em cascata sua config de pró-labore e seus recibos.
- **RN12:** Valor da config deve ser > 0.

### Recibos de pró-labore
- **RN13:** Só pode haver um recibo por sócio por competência por empresa (`UNIQUE partner_id + company_id + competencia`).
- **RN14:** `competencia` é armazenada como o primeiro dia do mês de referência (ex.: `2026-05-01`). A UI pode exibir apenas mês/ano.
- **RN15:** O valor do recibo é informado manualmente; não há cálculo automático a partir da config nesta entrega.
- **RN16:** Não há regra de fechamento de mês nesta entrega — o recibo é apenas um registro livre.

## 5. Critérios de aceite

### Empresa e sócios
- [ ] CA1: Usuário cria empresa com todos os campos obrigatórios e recebe de volta com `id` gerado.
- [ ] CA2: CNPJ inválido retorna 422 com campo `cnpj`.
- [ ] CA3: CNPJ duplicado para o mesmo `user_id` retorna 422 com campo `cnpj`.
- [ ] CA4: Sincronização de sócios com soma ≠ 100% retorna 422 com a soma atual na mensagem.
- [ ] CA5: Sincronização com soma = 100% persiste o quadro (insert/update/delete em lote).
- [ ] CA6: CPF de sócio inválido retorna 422 com campo `partners.N.cpf`.
- [ ] CA7: CPF duplicado na mesma empresa retorna 422.
- [ ] CA8: Usuário não acessa nem altera empresa de outro usuário (403).
- [ ] CA9: Exclusão de empresa remove sócios, configs e recibos em cascata.
- [ ] CA10: Listagem retorna apenas empresas do usuário, ordenadas por `razao_social`.

### Configuração de pró-labore
- [ ] CA11: Usuário cria config de pró-labore para um sócio e recebe `id` gerado.
- [ ] CA12: Config duplicada (mesmo sócio + empresa) retorna 422.
- [ ] CA13: Config para sócio de outra empresa retorna 422.
- [ ] CA14: Exclusão de sócio remove em cascata sua config de pró-labore.
- [ ] CA15: Listagem de configs retorna apenas as da empresa do usuário.

### Recibos de pró-labore
- [ ] CA16: Usuário registra recibo para sócio + competência e recebe `id` gerado.
- [ ] CA17: Recibo duplicado (mesmo sócio + empresa + competência) retorna 422.
- [ ] CA18: Recibo para sócio de outra empresa retorna 422.
- [ ] CA19: Usuário edita valor e observação de um recibo existente.
- [ ] CA20: Listagem de recibos aceita filtro por `competencia` (mês/ano) e retorna apenas os da empresa do usuário.
- [ ] CA21: Exclusão de sócio remove em cascata seus recibos.

## 6. Escopo

**Dentro:**
- CRUD de empresa (razão social, nome fantasia, CNPJ, regime tributário).
- Sincronização em lote do quadro societário (nome, CPF, percentual).
- Validações de CNPJ, CPF e soma de participações.
- CRUD de configuração de pró-labore por sócio.
- CRUD de recibos de pró-labore por sócio e competência.

**Fora (nesta entrega):**
- Regra de fechamento de mês (a ser implementada em feature futura).
- Geração automática de recibos a partir de config + mês.
- Cálculo de INSS, IRRF ou outros encargos sobre pró-labore.
- Integração com APIs da Receita Federal.
- Compartilhamento de empresa entre usuários.
- Histórico de alterações societárias.
- Vínculo de empresa com transações financeiras (feature futura).
- Upload de documentos societários.

## 7. Modelo de dados proposto

```
companies
─────────────────────────────────────────
id                bigint PK
user_id           bigint FK → users        not null, index
razao_social      varchar(255)             not null
nome_fantasia     varchar(255)             not null
cnpj              varchar(14)              not null  -- apenas dígitos
regime_tributario enum('mei','simples_nacional',
                       'lucro_presumido','lucro_real') not null
created_at        timestamp
updated_at        timestamp

UNIQUE (user_id, cnpj)


company_partners
─────────────────────────────────────────
id               bigint PK
company_id       bigint FK → companies    cascade delete
user_id          bigint FK → users        not null, index
nome             varchar(255)             not null
cpf              varchar(11)              not null  -- apenas dígitos
participacao     decimal(5,2)             not null  -- ex.: 33.33
created_at       timestamp
updated_at       timestamp

UNIQUE (company_id, cpf)


prolabore_configs
─────────────────────────────────────────
id               bigint PK
company_id       bigint FK → companies    cascade delete
partner_id       bigint FK → company_partners  cascade delete
user_id          bigint FK → users        not null, index
valor            decimal(10,2)            not null  -- valor mensal configurado
created_at       timestamp
updated_at       timestamp

UNIQUE (company_id, partner_id)


prolabore_records                         -- os recibos
─────────────────────────────────────────
id               bigint PK
company_id       bigint FK → companies    cascade delete
partner_id       bigint FK → company_partners  cascade delete
user_id          bigint FK → users        not null, index
competencia      date                     not null  -- dia 01 do mês (ex.: 2026-05-01)
valor            decimal(10,2)            not null  -- informado manualmente
observacao       text                     nullable
created_at       timestamp
updated_at       timestamp

UNIQUE (company_id, partner_id, competencia)
```

> `user_id` nas tabelas filhas é redundante via FK transitiva, mas mantém o padrão de isolamento explícito e viabiliza queries diretas sem JOIN.

## 8. Contrato preliminar (rascunho)

### Empresa
| Método | Rota | Descrição | Policy |
|--------|------|-----------|--------|
| `GET` | `/companies` | Lista empresas do usuário | por `user_id` |
| `POST` | `/companies` | Cria empresa | — |
| `GET` | `/companies/{company}` | Exibe empresa + sócios | `CompanyPolicy@view` |
| `PUT` | `/companies/{company}` | Atualiza dados da empresa | `CompanyPolicy@update` |
| `DELETE` | `/companies/{company}` | Remove empresa e cascata | `CompanyPolicy@delete` |
| `PUT` | `/companies/{company}/partners` | Sincroniza quadro societário em lote | `CompanyPolicy@update` |

### Configuração de pró-labore
| Método | Rota | Descrição | Policy |
|--------|------|-----------|--------|
| `GET` | `/companies/{company}/prolabore-configs` | Lista configs | `CompanyPolicy@view` |
| `POST` | `/companies/{company}/prolabore-configs` | Cria config para um sócio | `CompanyPolicy@update` |
| `PUT` | `/companies/{company}/prolabore-configs/{config}` | Atualiza valor | `ProlaboreConfigPolicy@update` |
| `DELETE` | `/companies/{company}/prolabore-configs/{config}` | Remove config | `ProlaboreConfigPolicy@delete` |

### Recibos de pró-labore
| Método | Rota | Descrição | Policy |
|--------|------|-----------|--------|
| `GET` | `/companies/{company}/prolabore-records` | Lista recibos (filtro: `?competencia=YYYY-MM`) | `CompanyPolicy@view` |
| `POST` | `/companies/{company}/prolabore-records` | Registra recibo | `CompanyPolicy@update` |
| `PUT` | `/companies/{company}/prolabore-records/{record}` | Atualiza valor/observação | `ProlaboreRecordPolicy@update` |
| `DELETE` | `/companies/{company}/prolabore-records/{record}` | Remove recibo | `ProlaboreRecordPolicy@delete` |

> Sincronização de sócios usa `PUT` (substitui o quadro completo). Valida soma = 100% quando `partners` não está vazio.
> Recibos não têm regra de fechamento; são registros livres que servirão de base para a feature de fechamento de mês futura.

## 9. Dependências e riscos

- **Dependência futura:** feature de fechamento de mês usará `prolabore_records` como ponto de entrada; o schema deve ser estável.
- **Dependência futura:** transações financeiras referenciarão `companies.id`.
- **Risco:** validação de CNPJ/CPF sem lib externa — implementar como Value Objects com o algoritmo de dígitos verificadores.
- **Risco:** precisão decimal em somas de participação — usar `decimal(5,2)` e comparar com `bccomp` no backend.

## 10. Handoff

- [x] Spec aprovada pelo usuário
- [x] `docs/progress/STATUS.md` atualizado
- [x] `/backend` entregue — código, feature tests, contrato publicado
- [x] `/tester` entregue — unit tests em `tests/Unit/Companies/`
- [x] `/frontend` — verificação visual no navegador validada pelo usuário em 2026-05-26
- [x] `done`
