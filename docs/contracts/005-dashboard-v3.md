# Contrato: Dashboard V3 — Pró-labore Real + Fator R

- **Bounded Context:** Dashboard + Companies
- **Spec de origem:** `docs/specs/005-dashboard-v3.md`
- **Status:** publicado
- **Autor:** /gerente
- **Data:** 2026-06-04
- **Revisões:**
  - 2026-06-04 — publicação inicial.

> **Transporte:** Inertia.js (page load). O endpoint `GET /dashboard` recebe um novo prop `prolaborePorEmpresa`.
> Este contrato é um delta sobre `004-dashboard-v2.md`.

---

## 1. Endpoint alterado — GET /dashboard

- **Auth:** obrigatória (`auth`, `verified`) — sem alteração.
- **Wayfinder:** `dashboard` — sem alteração.

**Response 200 — novo campo no payload:**

```json
{
  "companies": [ /* igual contratos anteriores */ ],
  "faturamentoPorEmpresa": { /* igual contrato 004 */ },
  "prolaborePorEmpresa": {
    "1": {
      "2026-05": {
        "total": 10000.00,
        "tipo": "recibo_manual",
        "fator_r": {
          "percentual": 0.312,
          "dentro": true,
          "estimado": false,
          "rbt12": 32000.00,
          "folha12": 9980.00
        },
        "socios": [
          { "nome": "João Silva", "valor": 5000.00, "tipo": "recibo_manual" },
          { "nome": "Maria Souza", "valor": 5000.00, "tipo": "recibo_automatico" }
        ]
      },
      "2026-06": {
        "total": 10000.00,
        "tipo": "previsao",
        "fator_r": {
          "percentual": 0.298,
          "dentro": true,
          "estimado": false,
          "rbt12": 33600.00,
          "folha12": 10000.00
        },
        "socios": [
          { "nome": "João Silva", "valor": 5000.00, "tipo": "previsao" },
          { "nome": "Maria Souza", "valor": 5000.00, "tipo": "previsao" }
        ]
      },
      "2026-03": {
        "total": 0,
        "tipo": "sem_faturamento",
        "fator_r": null,
        "socios": []
      }
    }
  }
}
```

---

## 2. Valores possíveis de `tipo`

| Valor | Quando | O que exibir |
|-------|--------|--------------|
| `recibo_manual` | Mês passado, registro com `origem='manual'` | Valor real · badge "lançado manualmente" |
| `recibo_automatico` | Mês passado, registro com `origem='automatico'` | Valor real · badge "gerado automaticamente" |
| `sem_recibo` | Mês passado, tem faturamento real, sem registro | Valor da config · badge "sem recibo" |
| `previsao` | Mês aberto/futuro com faturamento (real ou simulado) | Valor da config · badge "prévia" |
| `sem_faturamento` | Sem faturamento no mês | `total = 0`, sem socios · badge "sem faturamento" |
| `sem_config` | Tem faturamento, mas nenhum sócio tem config | `total = 0`, sem socios · badge "sem configuração" |

---

## 3. Objeto `fator_r`

Presente apenas para empresas com `regime_tributario = 'simples_nacional'` e `rbt12 > 0`. `null` nos demais casos.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `percentual` | `number` | Fator R (0.0–n); ex: `0.312` = 31.2% |
| `dentro` | `boolean` | `true` quando `percentual >= 0.28` (Anexo III) |
| `estimado` | `boolean` | `true` quando calculado com dados projetados (meses futuros ou configs como proxy) |
| `rbt12` | `number` | Soma de `valor_brl` dos 12 meses anteriores à competência (BRL) |
| `folha12` | `number` | Soma de pró-labore dos 12 meses anteriores à competência (BRL) |

**Fórmula:** `percentual = folha12 / rbt12`
**Dentro do Fator R:** `percentual >= 0.28` → Anexo III (mais favorável)

---

## 4. Contratos internos (backend)

### Migration — `prolabore_records`

```sql
ALTER TABLE prolabore_records
  ADD COLUMN origem VARCHAR(20) NOT NULL DEFAULT 'manual'
  CONSTRAINT prolabore_records_origem_check CHECK (origem IN ('manual', 'automatico'));

CREATE INDEX prolabore_records_company_competencia
  ON prolabore_records (company_id, competencia);
```

### ProlaboreRecord — domínio

Adicionar `origem: string` (`'manual'`|`'automatico'`) ao value object e ao `fromPersistence` / `create` / `update`.

### GetProlaboreDashboardUseCase

```
Src\Companies\Application\UseCases\ProlaboreRecord\GetProlaboreDashboardUseCase
  __invoke(int $companyId, int $userId, array $competencias): array
```

Retorno: mesmo shape de `prolaborePorEmpresa[companyId]` — `array<string, ProlaboreDashboardData>`.

Responsabilidades:
1. Carregar `prolabore_records` da janela de 13 competências + 12 meses anteriores (para Fator R).
2. Carregar `prolabore_configs` + nomes de `company_partners`.
3. Carregar faturamento real e simulado da janela de 25 meses (13 + 12 para Fator R).
4. Para cada competência: classificar tipo e calcular Fator R.
5. Verificar `regime_tributario` da empresa para decidir se inclui `fator_r`.

### GenerateProlaboreRecordsCommand

```
app/Console/Commands/GenerateProlaboreRecordsCommand.php
  php artisan prolabore:generate-records [--dry-run]
```

Scheduling em `routes/console.php`:
```php
Schedule::command('prolabore:generate-records')->dailyAt('01:00');
```

Lógica (executa apenas quando `today()->day === 1`):
```
Para cada empresa do sistema:
  competencia_anterior = primeiro dia do mês anterior
  Se SUM(invoices.valor_brl) WHERE company_id AND is_simulation=false AND deleted_at IS NULL
     AND data_emissao BETWEEN competencia_anterior AND fim_do_mes_anterior > 0:
    Para cada prolabore_config da empresa:
      Se NÃO existe prolabore_record WHERE company_id AND partner_id AND competencia = competencia_anterior:
        Criar record com valor=config.valor, origem='automatico'
```

### DashboardController

Adicionar injeção de `GetProlaboreDashboardUseCase` e popular `prolaborePorEmpresa`:

```php
$prolaborePorEmpresa = [];
foreach ($companies as $company) {
    $prolaborePorEmpresa[(string) $company['id']] =
        $getProlabore($company['id'], $userId, $competencias);
}
$payload['prolaborePorEmpresa'] = $prolaborePorEmpresa;
```

> **Nota:** A feature 004 tem achado PERF-01 aberto (N+1). Ao implementar a 005 com o mesmo padrão, ambos devem ser corrigidos juntos com a query multi-empresa, consolidando `GetFaturamentoDashboardUseCase` e `GetProlaboreDashboardUseCase` em uma única chamada ou em queries `whereIn`.

---

## 5. Tipos TypeScript

```typescript
// resources/js/types/dashboard.ts — adicionar:

export type ProlaboreTipo =
  | 'recibo_manual'
  | 'recibo_automatico'
  | 'sem_recibo'
  | 'previsao'
  | 'sem_faturamento'
  | 'sem_config';

export interface FatorR {
  percentual: number;   // ex: 0.312
  dentro: boolean;      // >= 0.28
  estimado: boolean;
  rbt12: number;
  folha12: number;
}

export interface SocioProlaboreDashboard {
  nome: string;
  valor: number;
  tipo: ProlaboreTipo;
}

export interface ProlaboreDashboardData {
  total: number;
  tipo: ProlaboreTipo;
  fator_r: FatorR | null;
  socios: SocioProlaboreDashboard[];
}

// Estender DashboardProps:
export interface DashboardProps {
  companies: Company[];
  faturamentoPorEmpresa: Record<string, Record<string, FaturamentoData>>;
  prolaborePorEmpresa: Record<string, Record<string, ProlaboreDashboardData>>;
}
```

---

## 6. Frontend — ProlaboreCard

### Estado vazio padrão

```typescript
const EMPTY_PROLABORE: ProlaboreDashboardData = {
  total: 0,
  tipo: 'sem_faturamento',
  fator_r: null,
  socios: [],
};
```

### Lookup

```typescript
const currentProlabore = useMemo(
  () =>
    (selectedCompanyId !== null
      ? prolaborePorEmpresa[String(selectedCompanyId)]?.[selectedMonthKey]
      : undefined) ?? EMPTY_PROLABORE,
  [prolaborePorEmpresa, selectedCompanyId, selectedMonthKey],
);
```

### Badge de tipo (recomendações visuais)

| `tipo` | Cor | Label |
|--------|-----|-------|
| `recibo_manual` | verde | "Lançado manualmente" |
| `recibo_automatico` | azul | "Gerado automaticamente" |
| `sem_recibo` | âmbar | "Sem recibo" |
| `previsao` | cinza | "Prévia" |
| `sem_faturamento` | cinza claro | "Sem faturamento" |
| `sem_config` | âmbar | "Sem configuração" |

### Badge Fator R

```
Se fator_r !== null:
  Chip verde "Fator R X,X% ✓" quando dentro = true
  Chip vermelho "Fator R X,X% ✗" quando dentro = false
  + sufixo "(estimado)" quando estimado = true
```

### Remoção do mock

O `MOCK_DASHBOARD.prolabore` não é mais usado. Remover do fixture ou marcar como `@deprecated`.

---

## 7. Observações de performance

- A janela de dados necessária ao backend é de **25 meses** (13 exibidos + 12 de histórico para Fator R).
- Consolidar com a correção PERF-01 da feature 004: uma única query `whereIn(company_id)` para prolabore, outra para faturamento.
- Índice `(company_id, competencia)` em `prolabore_records` cobre o lookup principal.
- Índice `(company_id, data_emissao)` já existe em `invoices` (feature 002).
