# Contrato: Empresa, Sócios e Pró-labore

- **Bounded Context:** `Companies`
- **Spec de origem:** `docs/specs/001-companies.md`
- **Status:** publicado
- **Autor:** /backend
- **Data:** 2026-05-25

> Esta é a **fonte da verdade** entre backend e frontend. O frontend só confia no que está aqui.
> Toda operação é autenticada e isolada por `user_id` via Policy.
> **Transporte:** o app usa **Inertia.js** — leituras chegam como **props de página** (`Inertia::render`) e
> escritas respondem com **redirect + flash**. O mesmo controller devolve **JSON** quando o cliente manda
> `Accept: application/json`. Os schemas abaixo descrevem o **shape dos dados** (idêntico em props e JSON).

---

## Endpoints — Empresa

### GET /companies
- **Descrição:** Lista todas as empresas do usuário autenticado, ordenadas por `razao_social`.
- **Auth:** obrigatória · **Policy:** isolamento por `user_id`
- **Wayfinder:** `companies.index`
- **Transporte:** Inertia (`Companies/Index` + props) · JSON

**Response 200**
```json
[
  {
    "id": 1,
    "razao_social": "Acme Ltda",
    "nome_fantasia": "Acme",
    "cnpj": "11222333000181",
    "cnpj_formatted": "11.222.333/0001-81",
    "regime_tributario": "simples_nacional",
    "regime_tributario_label": "Simples Nacional"
  }
]
```

---

### POST /companies
- **Descrição:** Cria uma nova empresa para o usuário autenticado.
- **Auth:** obrigatória
- **Wayfinder:** `companies.store`
- **Transporte:** Inertia (redirect → `companies.index` + flash) · JSON (201)

**Request**
```json
{
  "razao_social": "string|required|max:255",
  "nome_fantasia": "string|required|max:255",
  "cnpj": "string|required|14 dígitos|único por usuário|válido por dígitos verificadores",
  "regime_tributario": "enum|required: mei, simples_nacional, lucro_presumido, lucro_real"
}
```

**Response 201** _(JSON)_
```json
{
  "id": 1,
  "razao_social": "Acme Ltda",
  "nome_fantasia": "Acme",
  "cnpj": "11222333000181",
  "cnpj_formatted": "11.222.333/0001-81",
  "regime_tributario": "simples_nacional",
  "regime_tributario_label": "Simples Nacional"
}
```

**Erros**
| Status | Quando | Campo |
|--------|--------|-------|
| 422 | CNPJ inválido (dígitos verificadores) | `cnpj` |
| 422 | CNPJ duplicado para o mesmo usuário | `cnpj` |
| 422 | `regime_tributario` fora do enum | `regime_tributario` |

---

### GET /companies/{company}
- **Descrição:** Exibe os dados de uma empresa do usuário.
- **Auth:** obrigatória · **Policy:** `CompanyPolicy@view`
- **Wayfinder:** `companies.show`
- **Transporte:** Inertia (`Companies/Show` + props) · JSON

**Response 200**
```json
{
  "id": 1,
  "razao_social": "Acme Ltda",
  "nome_fantasia": "Acme",
  "cnpj": "11222333000181",
  "cnpj_formatted": "11.222.333/0001-81",
  "regime_tributario": "simples_nacional",
  "regime_tributario_label": "Simples Nacional"
}
```

**Erros**
| Status | Quando |
|--------|--------|
| 403 | Empresa pertence a outro usuário |
| 404 | Empresa não existe |

---

### PUT /companies/{company}
- **Descrição:** Atualiza os dados de uma empresa.
- **Auth:** obrigatória · **Policy:** `CompanyPolicy@update`
- **Wayfinder:** `companies.update`
- **Transporte:** Inertia (redirect → back + flash) · JSON (200)

**Request** _(mesmos campos do POST)_
```json
{
  "razao_social": "string|required|max:255",
  "nome_fantasia": "string|required|max:255",
  "cnpj": "string|required|14 dígitos|válido",
  "regime_tributario": "enum|required"
}
```

**Response 200** _(JSON — mesmo shape do GET)_

---

### DELETE /companies/{company}
- **Descrição:** Remove a empresa e todos os seus sócios, configs e recibos (cascata).
- **Auth:** obrigatória · **Policy:** `CompanyPolicy@delete`
- **Wayfinder:** `companies.destroy`
- **Transporte:** Inertia (redirect → `companies.index` + flash) · JSON (200)

**Response 200** _(JSON)_
```json
{ "message": "Empresa excluída com sucesso." }
```

---

## Endpoints — Sócios

### PUT /companies/{company}/partners
- **Descrição:** Sincroniza o quadro societário completo (substitui todos os sócios). Aceita array vazio para zerar.
- **Auth:** obrigatória · **Policy:** `CompanyPolicy@update`
- **Wayfinder:** `companies.partners.sync`
- **Transporte:** Inertia (redirect → back + flash) · JSON (200)

**Request**
```json
{
  "partners": [
    {
      "nome": "string|required|max:255",
      "cpf": "string|required|11 dígitos|válido por dígitos verificadores",
      "participacao": "number|required|gt:0|lte:100"
    }
  ]
}
```

**Response 200** _(JSON)_
```json
{ "message": "Sócios sincronizados com sucesso." }
```

**Erros**
| Status | Quando | Mensagem |
|--------|--------|---------|
| 422 | CPF inválido em qualquer sócio | `DomainException` com o CPF problemático |
| 422 | CPF duplicado na lista enviada | `DomainException` com o CPF duplicado |
| 422 | Soma das participações ≠ 100% | `DomainException` com a soma atual (ex.: "atual: 90.00%") |

> **Nota:** a validação de soma só ocorre quando `partners` não está vazio. Array vazio é aceito.

---

## Endpoints — Configuração de Pró-labore

### GET /companies/{company}/prolabore-configs
- **Descrição:** Lista as configs de pró-labore de todos os sócios da empresa.
- **Auth:** obrigatória · **Policy:** `CompanyPolicy@view`
- **Wayfinder:** `companies.prolabore-configs.index`
- **Transporte:** Inertia (`Companies/ProlaboreConfigs/Index` + props) · JSON

**Response 200**
```json
[
  {
    "id": 1,
    "company_id": 1,
    "partner_id": 2,
    "user_id": 3,
    "valor": 3000.00
  }
]
```

---

### POST /companies/{company}/prolabore-configs
- **Descrição:** Cria a config de pró-labore para um sócio (único por sócio+empresa).
- **Auth:** obrigatória · **Policy:** `CompanyPolicy@update`
- **Wayfinder:** `companies.prolabore-configs.store`
- **Transporte:** Inertia (redirect + flash) · JSON (201)

**Request**
```json
{
  "partner_id": "integer|required|único por empresa",
  "valor": "number|required|gt:0"
}
```

**Response 201** _(JSON — mesmo shape do GET)_

**Erros**
| Status | Quando |
|--------|--------|
| 422 | `partner_id` já tem config nesta empresa |
| 422 | Sócio não pertence à empresa |

---

### PUT /companies/{company}/prolabore-configs/{config}
- **Descrição:** Atualiza o valor da config de pró-labore.
- **Auth:** obrigatória · **Policy:** `ProlaboreConfigPolicy@update`
- **Wayfinder:** `companies.prolabore-configs.update`
- **Transporte:** Inertia (redirect → back + flash) · JSON (200)

**Request**
```json
{ "valor": "number|required|gt:0" }
```

---

### DELETE /companies/{company}/prolabore-configs/{config}
- **Descrição:** Remove a config de pró-labore.
- **Auth:** obrigatória · **Policy:** `ProlaboreConfigPolicy@delete`
- **Wayfinder:** `companies.prolabore-configs.destroy`
- **Transporte:** Inertia (redirect + flash) · JSON (200)

---

## Endpoints — Recibos de Pró-labore

### GET /companies/{company}/prolabore-records
- **Descrição:** Lista os recibos da empresa. Aceita filtro opcional por competência.
- **Auth:** obrigatória · **Policy:** `CompanyPolicy@view`
- **Wayfinder:** `companies.prolabore-records.index`
- **Transporte:** Inertia (`Companies/ProlaboreRecords/Index` + props) · JSON
- **Query param:** `?competencia=YYYY-MM` (opcional)

**Response 200**
```json
[
  {
    "id": 1,
    "company_id": 1,
    "partner_id": 2,
    "user_id": 3,
    "competencia": "2026-05-01",
    "valor": 3000.00,
    "observacao": null
  }
]
```

---

### POST /companies/{company}/prolabore-records
- **Descrição:** Registra um recibo de pró-labore (único por sócio + empresa + competência).
- **Auth:** obrigatória · **Policy:** `CompanyPolicy@update`
- **Wayfinder:** `companies.prolabore-records.store`
- **Transporte:** Inertia (redirect + flash) · JSON (201)

**Request**
```json
{
  "partner_id": "integer|required",
  "competencia": "string|required|formato: YYYY-MM",
  "valor": "number|required|gt:0",
  "observacao": "string|nullable"
}
```

> `competencia` é enviada no formato `YYYY-MM` e armazenada como `YYYY-MM-01` (primeiro dia do mês).

**Response 201** _(JSON — mesmo shape do GET)_

**Erros**
| Status | Quando |
|--------|--------|
| 422 | Já existe recibo para este sócio + empresa + competência |
| 422 | Sócio não pertence à empresa |

---

### PUT /companies/{company}/prolabore-records/{record}
- **Descrição:** Atualiza valor e/ou observação de um recibo.
- **Auth:** obrigatória · **Policy:** `ProlaboreRecordPolicy@update`
- **Wayfinder:** `companies.prolabore-records.update`
- **Transporte:** Inertia (redirect → back + flash) · JSON (200)

**Request**
```json
{
  "competencia": "string|required|formato: YYYY-MM",
  "valor": "number|required|gt:0",
  "observacao": "string|nullable"
}
```

---

### DELETE /companies/{company}/prolabore-records/{record}
- **Descrição:** Remove um recibo de pró-labore.
- **Auth:** obrigatória · **Policy:** `ProlaboreRecordPolicy@delete`
- **Wayfinder:** `companies.prolabore-records.destroy`
- **Transporte:** Inertia (redirect + flash) · JSON (200)

---

## Tipos TypeScript (para o frontend)

```typescript
export type RegimeTributario =
  | 'mei'
  | 'simples_nacional'
  | 'lucro_presumido'
  | 'lucro_real';

export interface Company {
  id: number;
  razao_social: string;
  nome_fantasia: string;
  cnpj: string;
  cnpj_formatted: string;
  regime_tributario: RegimeTributario;
  regime_tributario_label: string;
}

export interface PartnerData {
  nome: string;
  cpf: string;
  participacao: number;
}

export interface ProlaboreConfig {
  id: number;
  company_id: number;
  partner_id: number;
  user_id: number;
  valor: number;
}

export interface ProlaboreRecord {
  id: number;
  company_id: number;
  partner_id: number;
  user_id: number;
  competencia: string; // 'YYYY-MM-01'
  valor: number;
  observacao: string | null;
}
```

## Notas de implementação relevantes ao frontend

- `competencia` é enviada no POST como `YYYY-MM` mas retornada como `YYYY-MM-01`; exibir apenas mês/ano na UI.
- CNPJ é enviado como 14 dígitos sem formatação; o backend devolve `cnpj` (bruto) e `cnpj_formatted` (com pontuação).
- Sync de sócios (`PUT /companies/{id}/partners`) substitui o quadro completo; enviar o array inteiro mesmo em edições parciais.
- Não há geração automática de recibos; o registro é sempre manual.
