# Contrato: Dashboard V2 — Faturamento Real por Competência

- **Bounded Context:** Dashboard
- **Spec de origem:** `docs/specs/004-dashboard-v2.md`
- **Status:** publicado
- **Autor:** /gerente
- **Data:** 2026-06-03
- **Revisões:**
  - 2026-06-03 — publicação inicial (post-hoc).

> **Transporte:** Inertia.js — a página recebe props no page load; sem XHR adicional.
> Este contrato é um **delta** sobre `003-dashboard.md`; o endpoint é o mesmo `GET /dashboard`,
> mas a resposta agora inclui `faturamentoPorEmpresa`.

---

## Endpoint alterado

### GET /dashboard

- **Auth:** obrigatória (`auth`, `verified`) — sem alteração
- **Wayfinder:** `dashboard` — sem alteração
- **Policy:** nenhuma adicional — `ListCompaniesUseCase` filtra por `user_id`; `faturamentoPorCompetencias` usa os `company_id` retornados por esse use case

**Response 200** _(props Inertia — delta em relação à feature 003)_

```json
{
  "companies": [ /* igual ao contrato 003 */ ],
  "faturamentoPorEmpresa": {
    "1": {
      "2026-05": {
        "total": 35000.00,
        "notas_emitidas": 4,
        "itens": [
          { "tipo": "nacional",       "valor": 25000.00, "quantidade": 3 },
          { "tipo": "internacional",  "valor": 10000.00, "quantidade": 1 }
        ]
      },
      "2026-06": {
        "total": 0,
        "notas_emitidas": 0,
        "itens": []
      }
    }
  }
}
```

**Erros** — sem alteração em relação ao contrato 003.

---

## Contratos internos (backend)

### InvoiceRepository — novo método

```php
/**
 * @param  string[]  $competencias  'YYYY-MM'
 * @return array<string, array{
 *   total: float,
 *   notas_emitidas: int,
 *   itens: list<array{tipo: string, valor: float, quantidade: int}>
 * }>
 */
public function faturamentoPorCompetencias(int $companyId, array $competencias): array;
```

- Inclui apenas notas reais (`is_simulation = false`, `deleted_at IS NULL`).
- Meses sem notas retornam `{ total: 0, notas_emitidas: 0, itens: [] }`.
- Uma única query `GROUP BY TO_CHAR(data_emissao, 'YYYY-MM'), tipo`.

### GetFaturamentoDashboardUseCase

```
Src\Invoicing\Application\UseCases\Invoice\GetFaturamentoDashboardUseCase
  __invoke(int $companyId, array $competencias): array
```

Invólucro fino sobre `InvoiceRepository::faturamentoPorCompetencias`.

---

## Tipos TypeScript

```typescript
// Sem alteração nos tipos existentes de FaturamentoData / FaturamentoItem.

// Extensão em DashboardProps (resources/js/types/dashboard.ts):
export interface DashboardProps {
  companies: Company[];
  /** companyId (string) → competencia 'YYYY-MM' → dados reais */
  faturamentoPorEmpresa: Record<string, Record<string, FaturamentoData>>;
}
```

**Lookup no frontend:**
```typescript
const faturamento =
  faturamentoPorEmpresa[String(selectedCompanyId)]?.[selectedMonthKey]
  ?? { total: 0, notas_emitidas: 0, itens: [] };
```

---

## Notas de implementação

- O controller calcula os 13 meses (`now() ± 6`) em PHP, idêntico à lógica JS do frontend.
- O `valor_brl` já está em BRL (notas internacionais têm conversão aplicada na emissão).
- A prop `faturamentoPorEmpresa` pode crescer com o número de empresas; para usuários com muitas empresas, considerar paginação ou lazy-load numa feature futura.
