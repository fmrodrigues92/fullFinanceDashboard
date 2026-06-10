# Contrato: Criar/Editar Recibo de Pró-labore Inline no Dashboard

- **Bounded Context:** Dashboard + Companies
- **Spec de origem:** `docs/specs/006-prolabore-dashboard-inline.md`
- **Status:** publicado
- **Autor:** /gerente (tech-lead)
- **Data:** 2026-06-04
- **Revisões:**
  - 2026-06-04 — publicação inicial.

> Fonte da verdade back↔front. Toda operação é autenticada e isolada por `user_id` via Policy.
> **Transporte:** Inertia.js — escritas respondem **redirect + flash**, com fallback **JSON** sob
> `Accept: application/json` (mesmo shape de dados). Este contrato é um **delta** sobre `005-dashboard-v3.md`.

---

## 1. Decisão de rota — reuso dos endpoints existentes

Os endpoints de `prolabore_records` **já existem** (feature 001) e são o padrão coerente do projeto
(REST aninhado por empresa, Policy por `CompanyModel`/`ProlaboreRecordModel`). **Não** criamos rotas em
`/dashboard/...`. O fluxo do dashboard **reutiliza**:

| Verbo | Rota | Wayfinder | Ação |
|-------|------|-----------|------|
| `POST` | `/companies/{company}/prolabore-records` | `companies.prolabore-records.store` | Criar recibo |
| `PUT` | `/companies/{company}/prolabore-records/{record}` | `companies.prolabore-records.update` | Editar recibo |

O `/backend` aplica os ajustes de comportamento abaixo (mês corrente, `origem='manual'` ao editar, redirect
compatível com o dashboard). O `/frontend` chama esses Wayfinders.

---

## 2. POST /companies/{company}/prolabore-records — criar

- **Descrição:** cria recibo de pró-labore para um sócio no **mês corrente**.
- **Auth:** obrigatória · **Policy:** `update` sobre `CompanyModel` (empresa do usuário).
- **Wayfinder:** `companies.prolabore-records.store`
- **Transporte:** Inertia (redirect + flash `toast`) · JSON (`201` com o recibo) sob `Accept: application/json`.

**Request**
```json
{
  "partner_id": "integer, required, deve pertencer a {company}",
  "competencia": "string, required, date_format:Y-m, DEVE ser o mês corrente",
  "valor": "numeric, required, gt:0",
  "observacao": "string, nullable, max:500"
}
```

> **Atenção ao formato:** `competencia` é `"YYYY-MM"` (ex.: `"2026-06"`), **não** `"YYYY-MM-01"`.
> O backend acrescenta `-01` internamente.

**Response 201 (JSON) / redirect+flash (Inertia)** — shape do recibo:
```json
{
  "id": 42,
  "company_id": 1,
  "partner_id": 7,
  "user_id": 3,
  "competencia": "2026-06-01",
  "valor": 5000.00,
  "observacao": null,
  "origem": "manual"
}
```

> **Delta sobre o present() atual:** adicionar `origem` ao payload do recibo presentado (hoje ausente).

No fluxo Inertia, o `store` deve **redirecionar de volta ao dashboard** (`redirect()->back()`), e **não** para
`companies.prolabore-records.index` quando a origem da requisição é o dashboard. Flash `toast` de sucesso mantido.

---

## 3. PUT /companies/{company}/prolabore-records/{record} — editar

- **Descrição:** atualiza o recibo de um sócio no mês corrente. Marca `origem = 'manual'`.
- **Auth:** obrigatória · **Policy:** `update` sobre `ProlaboreRecordModel`; `404` se `record.company_id != company.id`.
- **Wayfinder:** `companies.prolabore-records.update`
- **Transporte:** Inertia (redirect+flash) · JSON (`200` com o recibo).

**Request**
```json
{
  "competencia": "string, required, date_format:Y-m, DEVE ser o mês corrente",
  "valor": "numeric, required, gt:0",
  "observacao": "string, nullable, max:500"
}
```

> `competencia` permanece obrigatória no update (comportamento atual do `UpdateProlaboreRecordRequest`).
> O frontend reenvia a mesma competência do mês corrente.

**Response 200** — mesmo shape do recibo da seção 2, com `origem: "manual"` após a edição (RN8).

---

## 4. Erros (ambos endpoints)

| Status | Quando | Corpo (JSON) |
|--------|--------|--------------|
| 422 | `valor <= 0`, `competencia` fora do mês corrente, `partner_id` não pertence à empresa, ou duplicata `(company,partner,competencia)` | `{ "message": "...", "errors": { "campo": ["..."] } }` |
| 403 | empresa/recibo de outro usuário (Policy) | `{ "message": "This action is unauthorized." }` |
| 404 | `record` não pertence à `company`, ou empresa/recibo inexistente | `{ "message": "..." }` |

Mensagens de validação esperadas:
- duplicata → `"Já existe um recibo para este sócio nesta competência."`
- competência fora do mês corrente → `"Só é possível lançar o pró-labore do mês corrente por aqui."`
- partner fora da empresa → `"O sócio informado não pertence a esta empresa."`

No Inertia, validações chegam como `errors` na página (via `redirect()->back()->withErrors(...)`).

---

## 5. Delta no payload do dashboard (GET /dashboard)

Para o frontend saber **a qual sócio** o controle pertence e **se cria ou edita**, cada item de `socios`
ganha dois campos. Delta mínimo sobre o contrato 005 — demais campos inalterados.

**Antes (005):**
```json
{ "nome": "João Silva", "valor": 5000.00, "tipo": "previsao" }
```

**Agora (006):**
```json
{ "nome": "João Silva", "valor": 5000.00, "tipo": "previsao", "partner_id": 7, "record_id": null }
```

Regras de preenchimento:
- `partner_id` — sempre presente (id do sócio).
- `record_id` — `number` quando existe `prolabore_record` para aquele sócio/competência; `null` caso contrário.
  - `record_id !== null` → o controle inline edita (PUT `{record}`).
  - `record_id === null` → o controle inline cria (POST).

> **Backend:** `GetProlaboreDashboardUseCase` já carrega `partner_id` (chave de `compRecords`/`compConfigs`)
> e os records carregam o id. Hoje o método não propaga `partner_id`/`record_id` para os `socios` —
> esta feature passa a propagá-los. O shape de `total`, `tipo`, `fator_r` é inalterado.

Exemplo de `prolaborePorEmpresa["1"]["2026-06"]` (mês corrente):
```json
{
  "total": 10000.00,
  "tipo": "previsao",
  "fator_r": { "percentual": 0.298, "dentro": true, "estimado": false, "rbt12": 33600.00, "folha12": 10000.00 },
  "socios": [
    { "nome": "João Silva", "valor": 5000.00, "tipo": "previsao", "partner_id": 7, "record_id": null },
    { "nome": "Maria Souza", "valor": 5000.00, "tipo": "recibo_manual", "partner_id": 8, "record_id": 91 }
  ]
}
```

---

## 6. Tipos TypeScript

```typescript
// resources/js/types/dashboard.ts — estender SocioProlaboreDashboard:

export interface SocioProlaboreDashboard {
  nome: string;
  valor: number;
  tipo: ProlaboreTipo;
  partner_id: number;        // NOVO — id do sócio
  record_id: number | null;  // NOVO — id do recibo se existir; null = criar
}

// Payloads de escrita (espelham seções 2 e 3):

export interface CreateProlaboreRecordPayload {
  partner_id: number;
  competencia: string;       // 'YYYY-MM' (mês corrente)
  valor: number;             // > 0
  observacao: string | null; // max 500
}

export interface UpdateProlaboreRecordPayload {
  competencia: string;       // 'YYYY-MM' (mês corrente)
  valor: number;             // > 0
  observacao: string | null;
}

export interface ProlaboreRecord {
  id: number;
  company_id: number;
  partner_id: number;
  user_id: number;
  competencia: string;       // 'YYYY-MM-01'
  valor: number;
  observacao: string | null;
  origem: 'manual' | 'automatico';
}
```

---

## 7. Frontend — comportamento esperado (ProlaboreCard)

Renderiza o controle inline **somente** quando a competência selecionada é o mês corrente
(`selectedMonthKey === CURRENT_MONTH_KEY`).

Por sócio do `socios`:
- **Input numérico (R$)** pré-preenchido com `socio.valor` (já em BRL — RN4/RN6). Vazio quando não há valor base.
- **Textarea curto opcional** para `observacao` (máx. 500).
- **Botão "Salvar"**:
  - `socio.record_id === null` → POST `companies.prolabore-records.store({ company })` com `CreateProlaboreRecordPayload`
    (`partner_id = socio.partner_id`, `competencia = selectedMonthKey`).
  - `socio.record_id !== null` → PUT `companies.prolabore-records.update({ company, record: socio.record_id })`
    com `UpdateProlaboreRecordPayload`.
- **Loading:** desabilitar input/botão durante a requisição.
- **Erro:** exibir `errors` (validação 422) ou mensagem genérica (500) junto ao controle do sócio.
- **Sucesso:** atualização parcial do dashboard:
  ```ts
  router.reload({ only: ['prolaborePorEmpresa'] })
  ```
  Após o reload, o card reflete novo `valor`, novo `tipo` do sócio (`recibo_manual`), `record_id` populado e
  Fator R recalculado. Toast de sucesso vem via flash do Inertia.

Estados sem controle:
- `tipo === 'sem_faturamento'` ou `'sem_config'` no mês corrente → sem `socios`, logo sem controle inline.

> **Wayfinder:** usar as rotas tipadas geradas para `companies.prolabore-records.store/update`.
> A submissão deve sinalizar a origem dashboard para o redirect `back()` (ex.: `router.post/put` mantém a
> página atual; o backend usa `redirect()->back()`).

---

## 8. Notas de implementação

- **Sem migration.** Reuso integral de `prolabore_records`.
- **Backend, ajustes pontuais:**
  1. `present()` do `ProlaboreRecordController` passa a incluir `origem`.
  2. `StoreProlaboreRecordRequest` e `UpdateProlaboreRecordRequest`: adicionar regra de **mês corrente**
     em `competencia` e `max:500` em `observacao`; validar que `partner_id` pertence à empresa no store.
  3. `update` marca `origem = 'manual'` (hoje preserva). Decisão de negócio RN8 — ajustar
     `ProlaboreRecord::update()` ou o UseCase para forçar `'manual'`.
  4. `store` (fluxo Inertia): `redirect()->back()` quando vindo do dashboard, preservando o `index` para a tela de pró-labore.
  5. `GetProlaboreDashboardUseCase`: propagar `partner_id` e `record_id` em cada `socio`.
- **Read-only:** controle inline ausente fora do mês corrente; nenhuma chamada de escrita por mês passado/futuro.
- **Mock-friendly:** as seções 2, 3 e 5 bastam para o `/frontend` gerar fixtures (recibo, payloads, sócio com/sem `record_id`).
