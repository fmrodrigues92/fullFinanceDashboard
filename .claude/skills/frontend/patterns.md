# Padrões de código — Frontend

Referência canônica de **forma**. Copie estrutura/tipos/ganchos — não o domínio.
A página depende do **contrato** (props Inertia tipadas), nunca inventa a API.

> **Direção:** página grande é cheiro de falta de componentização. Se um `Index.tsx` passar de ~150 linhas, é hora
> de extrair componentes de feature, hooks e formatadores. A página deve **orquestrar**, não desenhar tudo.

---

## 1. Estrutura por feature (co-localização)

Páginas Inertia vivem em `resources/js/pages/{Context}/`. O que é **da feature** mora **junto**:

```
resources/js/
├── pages/Invoicing/Invoices/
│   ├── Index.tsx                  ← página: orquestra; props do contrato; ~100–150 linhas
│   ├── components/                ← componentes só desta feature
│   │   ├── invoice-table.tsx
│   │   ├── invoice-filters.tsx
│   │   └── create-invoice-dialog.tsx
│   ├── hooks/
│   │   └── use-invoice-filters.ts ← lógica local reutilizada na própria feature
│   └── lib/
│       └── invoice-format.ts      ← formatadores puros (BRL, datas) específicos
├── components/                    ← componentes **globais** (cross-feature): app-shell, breadcrumbs, alert-error...
├── components/ui/                 ← shadcn/ui (não editar manualmente; só compor)
├── hooks/                         ← hooks globais (use-mobile, use-clipboard...)
├── lib/utils.ts                   ← util global (cn, etc.)
├── types/                         ← tipos espelhando contratos (`invoicing.ts`, `companies.ts`)
├── routes/                        ← Wayfinder (gerado; não editar)
└── __fixtures__/{Context}/        ← mocks do contrato para desenvolvimento em paralelo com /backend
```

**Regra de promoção:** começa local → vira global só na **terceira feature** que precisar dele (DRY com juízo).
Mover é barato; abstrair cedo é caro.

---

## 2. Convenções (alinhadas ao repo)

- **Arquivos:** `kebab-case.tsx` para componentes (`invoice-table.tsx`, `alert-error.tsx`), `kebab-case.ts(x)` para hooks (`use-flash-toast.ts`). Páginas Inertia ficam `PascalCase.tsx` (`Index.tsx`, `Show.tsx`) porque o nome casa com a string passada ao `Inertia::render`.
- **Componentes/Hooks:** `PascalCase` para componentes; `useFooBar` para hooks.
- **Exports:** `export default` na página Inertia (Inertia exige); **named exports** em componentes e hooks.
- **Imports:** `@/` sempre que possível (alias para `resources/js/`). Para componente local da feature, caminho relativo curto (`./components/invoice-table`).
- **Type-only imports:** `import type { Foo } from '...'` quando só o tipo for usado (evita ciclo e enxuga bundle).
- **Sem URL hardcoded.** Sempre helper do Wayfinder (`store()`, `update({ id })`, etc.). Roda `./vendor/bin/sail npm run dev` para regenerar.

---

## 3. Tipos espelhando o contrato (1:1)

`resources/js/types/invoicing.ts`:
```ts
export interface Invoice {
    id: number;
    client_id: number | null;
    tipo: InvoiceTipo;
    valor_brl: string;        // já formatado pelo back (Money::format)
    data_emissao: string;     // ISO
}

export type InvoiceTipo = 'servico' | 'produto';

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
```

Mudança no contrato? O tipo muda primeiro; o TS quebra onde precisa atualizar.

---

## 4. Página Inertia (orquestra, não desenha)

`resources/js/pages/Invoicing/Invoices/Index.tsx`:
```tsx
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { InvoiceTable } from './components/invoice-table';
import { InvoiceFilters } from './components/invoice-filters';
import { CreateInvoiceDialog } from './components/create-invoice-dialog';
import { useInvoiceFilters } from './hooks/use-invoice-filters';
import type { Invoice, PaginationMeta, ClientOption } from '@/types/invoicing';

interface PageProps {
    invoices: Invoice[];
    pagination: PaginationMeta;
    clients: ClientOption[];
    filters: { competencia: string; tipo: string };
    [key: string]: unknown;
}

export default function Index() {
    const { invoices, pagination, clients, filters } = usePage<PageProps>().props;
    const { active, apply, reset } = useInvoiceFilters(filters);

    return (
        <>
            <Head title="Notas fiscais" />
            <div className="flex items-center justify-between">
                <InvoiceFilters value={active} onChange={apply} onReset={reset} />
                <CreateInvoiceDialog clients={clients} />
            </div>
            <InvoiceTable invoices={invoices} pagination={pagination} />
        </>
    );
}

Index.layout = (page: React.ReactNode) => <AppLayout breadcrumbs={[{ title: 'Notas' }]}>{page}</AppLayout>;
```

A página: lê props, monta hooks, compõe componentes. **Sem JSX longo, sem regra de UI inline.**

---

## 5. Componentes de feature (apresentação)

`./components/invoice-table.tsx` — recebe dados via props, **não** lê `usePage`:
```tsx
import { Badge } from '@/components/ui/badge';
import { PaginationNav } from '@/components/ui/pagination-nav';
import type { Invoice, PaginationMeta } from '@/types/invoicing';

interface Props {
    invoices: Invoice[];
    pagination: PaginationMeta;
}

export function InvoiceTable({ invoices, pagination }: Props) {
    if (invoices.length === 0) {
        return <p className="text-muted-foreground">Nenhuma nota emitida ainda.</p>;
    }
    return (
        <div>
            <table>{/* ... */}</table>
            <PaginationNav meta={pagination} />
        </div>
    );
}
```

**Princípios:**
- **Presentational primeiro:** componente recebe tudo via props; quem busca dado é a página. Facilita teste, reuso, mock.
- **Estado vazio explícito.** Sempre. (Contrato lista cenários vazios — siga-os.)
- **Sem `any`.** Props sempre tipadas; use union/discriminated unions em vez de `string` solto.
- **Composição > config.** Em vez de prop `variant="big-and-red"`, exponha slots/children. shadcn/ui dá o vocabulário (`<Dialog><DialogHeader/>...`).
- **Acessibilidade vem de Radix/shadcn** — não reinvente menu/dialog/select à mão.

---

## 6. Forms (Inertia `useForm` + erros do contrato)

`./components/create-invoice-dialog.tsx`:
```tsx
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { store } from '@/routes/invoicing/invoices';
import type { ClientOption } from '@/types/invoicing';

interface Props { clients: ClientOption[]; }

export function CreateInvoiceDialog({ clients }: Props) {
    const form = useForm({ client_id: '', tipo: '', valor_brl: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild><Button>Nova nota</Button></DialogTrigger>
            <DialogContent>
                <DialogHeader><DialogTitle>Nova nota</DialogTitle></DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label htmlFor="valor_brl">Valor (BRL)</Label>
                        <Input id="valor_brl" value={form.data.valor_brl} onChange={(e) => form.setData('valor_brl', e.target.value)} />
                        <InputError message={form.errors.valor_brl} />
                    </div>
                    <DialogFooter><Button type="submit" disabled={form.processing}>Salvar</Button></DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
```

- **Erros:** `422` chega em `form.errors` (chaves do contrato); render via `<InputError />`. `403`/`404` viram toast/redirect via `onError` global.
- **`preserveScroll`** em listas; `form.reset()` no sucesso.
- **Validação no servidor é a verdade** — não duplique no front; use só hints de UX.

---

## 7. Hooks customizados (extração de lógica)

Quando uma página tem `useState`/`useEffect` longos com regra de UI, extraia para `./hooks/use-*.ts`:

```ts
// pages/Invoicing/Invoices/hooks/use-invoice-filters.ts
import { router } from '@inertiajs/react';
import { useState } from 'react';

export function useInvoiceFilters(initial: { competencia: string; tipo: string }) {
    const [active, setActive] = useState(initial);
    const apply = (next: typeof active) => {
        setActive(next);
        router.get(window.location.pathname, next, { preserveState: true, replace: true });
    };
    const reset = () => apply({ competencia: '', tipo: '' });
    return { active, apply, reset };
}
```

- **Um hook = uma responsabilidade.** Se nomear "useThingAndOtherThing", divida.
- **Hooks locais à feature** ficam em `pages/{Ctx}/{Feat}/hooks/`; **globais** (`use-mobile`, `use-clipboard`) ficam em `resources/js/hooks/`.

---

## 8. DRY com juízo

- **Regra das três repetições:** dois componentes parecidos = ok, três = extraia. Antes disso, é provavelmente acoplamento prematuro.
- **Formatadores puros** (`fmtBrl`, `fmtDate`) viram util da feature em `./lib/*.ts`; se três features usarem, sobem para `@/lib/`.
- **Não crie wrappers triviais** sobre shadcn ("MyButton" que só repassa props). Use o componente direto e componha.
- **Barrel files (`index.ts` re-exportando tudo) só quando o ganho de import for real** — não por hábito.

---

## 9. Estados que o contrato exige

Para cada listagem/recurso:
1. **Loading inicial** — o Inertia já entrega props prontas; loading é só em transições (`form.processing`, `router.visit`).
2. **Vazio** — mensagem do contrato (estados vazios são parte do shape: liste-os explicitamente).
3. **Erro 403/404** — não é exception silenciosa; redirect com flash ou toast (`useFlashToast`).
4. **Erro 422** — sempre em `form.errors`, exibido inline.

---

## 10. Fixtures para paralelismo com /backend

Enquanto o `/backend` ainda não publicou a rota, o frontend desenvolve contra fixtures tipadas:

```ts
// resources/js/__fixtures__/Invoicing/invoices.list.ts
import type { Invoice } from '@/types/invoicing';

export const invoicesFixture: Invoice[] = [
    { id: 1, client_id: 10, tipo: 'servico', valor_brl: 'R$ 1.000,00', data_emissao: '2026-05-01' },
];
```

Use em Storybook/playground/dev local. **Nunca importe fixture em código de produção** — ela existe para destravar o paralelismo, não para virar dado real.

---

## 11. O que **não** fazer

- ❌ Página com 400+ linhas de JSX + handlers + formatadores tudo junto.
- ❌ `useState` espalhado controlando dado que veio como prop Inertia.
- ❌ Componente "burro" que recebe `usePage()` direto — quebra teste e reuso.
- ❌ URL como string mágica (`/invoicing/invoices/${id}`) — use Wayfinder.
- ❌ Reimplementar componente de UI existente em `components/ui/`.
- ❌ Abstrair na primeira ocorrência (DRY especulativo).
- ❌ `any` ou `as Foo` para silenciar TS — o contrato é tipado por uma razão.
