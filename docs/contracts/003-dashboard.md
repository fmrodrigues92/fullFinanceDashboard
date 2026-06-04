# Contrato: Dashboard Financeiro por Empresa (Mocado)

- **Bounded Context:** Dashboard
- **Spec de origem:** `docs/specs/003-dashboard.md`
- **Status:** publicado
- **Autor:** /gerente (tech-lead)
- **Data:** 2026-05-29
- **Revisões:**
  - 2026-05-29 — publicação inicial.

> Esta é a **fonte da verdade** entre backend e frontend.
> **Transporte:** Inertia.js — a página recebe props do controller; sem chamadas XHR adicionais nesta feature.
> **Todos os dados de dashboard são fixtures client-side** — o único dado real é a lista de empresas.

---

## Endpoints

### GET /dashboard

- **Descrição:** Renderiza o dashboard financeiro mocado. Passa a lista de empresas do usuário como prop.
- **Auth:** obrigatória (`auth`, `verified`)
- **Policy:** nenhuma adicional — `ListCompaniesUseCase` já filtra por `user_id` internamente.
- **Wayfinder:** `dashboard`
- **Transporte:** Inertia (`Dashboard` page + props abaixo) · JSON (mesmo shape sob `Accept: application/json`)

**Request**
Sem parâmetros. Navegação de empresa e competência é 100% client-side (estado React).

**Response 200** _(props da página Inertia)_
```json
{
  "companies": [
    {
      "id": 1,
      "razao_social": "Tech Serviços Ltda",
      "nome_fantasia": "Tech",
      "cnpj": "12345678000195",
      "cnpj_formatted": "12.345.678/0001-95",
      "regime_tributario": "simples_nacional",
      "regime_tributario_label": "Simples Nacional"
    }
  ]
}
```

**Erros**
| Status | Quando | Corpo |
|--------|--------|-------|
| 401 | não autenticado | redirect para `/login` (Inertia) |
| 403 | e-mail não verificado | redirect para `/verify-email` (Inertia) |

---

## Implementação da rota (handoff para /frontend ou /backend)

A rota atual em `routes/web.php` usa `Route::inertia()` sem controller:
```php
// antes (sem props):
Route::inertia('dashboard', 'dashboard')->name('dashboard');
```

Deve ser convertida para um controller que passe `companies`:
```php
// depois:
Route::get('dashboard', DashboardController::class)->name('dashboard');
```

`app/Http/Controllers/DashboardController.php` (a criar):
```php
// injeta ListCompaniesUseCase (já existe em Src\Companies\Application\UseCases\)
// retorna: Inertia::render('Dashboard', ['companies' => $companies])
```

`ListCompaniesUseCase` já existe em `app/src/Companies/Application/UseCases/ListCompaniesUseCase.php`
e retorna `Company[]` filtrados por `user_id` do usuário autenticado.

---

## Lógica client-side (sem endpoints)

Toda navegação (empresa ↔ competência) e todos os dados de dashboard são geridos no React:

### Navegação de competência (13 cards)
```
Calculado no frontend a partir de `new Date()`:
  meses = [hoje - 6 meses, ..., hoje, ..., hoje + 6 meses]  → 13 itens
  card selecionado default = hoje (índice 6)
  label: "Mai/2026", "Jun/2026", etc. (pt-BR, mês abreviado 3 letras + ano 4 dígitos)
```

### Seletor de empresa
```
  Props recebidas: companies[]
  Estado local: selectedCompanyId (default = companies[0].id)
  Trocar empresa → atualiza MOCK_DASHBOARD com dados da empresa selecionada (ou fixture única reaproveitada)
```

### Fixtures
Exportadas de `resources/js/fixtures/dashboard.ts` como `MOCK_DASHBOARD: DashboardMockData`:
```
DAS:
  valor: 1234.56
  vencimento: "20/05/2026"
  status: "pendente"
  detalhes:
    - { descricao: "IRPJ", valor: 312.50 }
    - { descricao: "CSLL", valor: 280.00 }
    - { descricao: "PIS/COFINS", valor: 420.06 }
    - { descricao: "CPP", valor: 222.00 }

Pró-labore:
  total: 10000.00
  socios:
    - { nome: "João Silva", valor: 5000.00, status: "pago" }
    - { nome: "Maria Souza", valor: 5000.00, status: "pendente" }

Faturamento:
  total: 35000.00
  notas_emitidas: 4
  itens:
    - { tipo: "nacional", valor: 25000.00, quantidade: 3 }
    - { tipo: "internacional", valor: 10000.00, quantidade: 1 }

Gastos:
  total: 2000.00
  itens:
    - { categoria: "INSS", descricao: "INSS empregador", valor: 500.00 }
    - { categoria: "Cartão", descricao: "Cartão corporativo XP", valor: 1200.00 }
    - { categoria: "Taxas", descricao: "Outras taxas", valor: 300.00 }
```

---

## Tipos TypeScript (resources/js/types/dashboard.ts)

```typescript
export interface DasDetalhe {
  descricao: string;
  valor: number;
}

export interface DasData {
  valor: number;
  vencimento: string;   // formato 'DD/MM/YYYY'
  status: 'pendente' | 'pago';
  detalhes: DasDetalhe[];
}

export interface SocioProLabore {
  nome: string;
  valor: number;
  status: 'pago' | 'pendente';
}

export interface ProlaboreData {
  total: number;
  socios: SocioProLabore[];
}

export interface FaturamentoItem {
  tipo: 'nacional' | 'internacional';
  valor: number;
  quantidade: number;
}

export interface FaturamentoData {
  total: number;
  notas_emitidas: number;
  itens: FaturamentoItem[];
}

export interface GastoItem {
  categoria: string;
  descricao: string;
  valor: number;
}

export interface GastosData {
  total: number;
  itens: GastoItem[];
}

export interface DashboardMockData {
  das: DasData;
  prolabore: ProlaboreData;
  faturamento: FaturamentoData;
  gastos: GastosData;
}

// Props da página Dashboard (Inertia)
export interface DashboardProps {
  companies: import('./companies').Company[];
}
```

---

## Notas de implementação para o /frontend

### Componentes shadcn recomendados
- `Select` + `SelectTrigger` / `SelectContent` / `SelectItem` — seletor de empresa
- `Card` + `CardHeader` + `CardContent` — cada seção do dashboard
- `Collapsible` + `CollapsibleTrigger` + `CollapsibleContent` — expand/collapse inline
- `Badge` — status (pendente/pago) e label "dados simulados"
- `Button` variant ghost/outline — cards de competência

### Cards de competência
```tsx
// 13 botões/cards horizontais com scroll se necessário
// card ativo: borda/fundo destacado (ex.: bg-primary text-primary-foreground)
// card inativo: variant outline
// label: new Date(year, month).toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' })
//   → "mai. 2026" → normalizar para "Mai/2026"
```

### Formatação de moeda
```typescript
// Reutilizar padrão já existente no projeto (verificar se há helper em resources/js/)
const brl = (v: number) =>
  v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
```

### Badge "dados simulados"
Exibir na área do header da página ou acima dos cards com badge/chip amarelo/âmbar:
`"⚠ Dados simulados — valores fictícios para visualização do layout"`

### Estados vazios
- Se `companies` vier vazio: exibir mensagem de estado vazio com link para `/companies`
  (`"Nenhuma empresa cadastrada. Cadastre uma empresa para ver o dashboard."`)

### Expand/collapse por card
- Estado local React: `const [openSections, setOpenSections] = useState<Set<string>>(new Set())`
- Cada seção tem uma key (ex.: `'das'`, `'prolabore'`, `'faturamento'`, `'gastos'`)
- Toggle independente: adiciona/remove da Set

### Estrutura de arquivo sugerida
```
resources/js/
  fixtures/
    dashboard.ts          ← MOCK_DASHBOARD: DashboardMockData
  types/
    dashboard.ts          ← tipos TS acima
  pages/
    dashboard.tsx         ← página completa (seletor + cards competência + 4 seções)
```

### Reutilizar
- Layout: `app-sidebar-layout.tsx` (padrão das demais páginas)
- Tipo `Company`: importar de `@/types/companies`
- Breadcrumbs: `[{ title: 'Dashboard', href: route('dashboard') }]`
