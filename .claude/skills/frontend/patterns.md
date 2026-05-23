# Padrões de código — Frontend

Referência canônica de **forma**. Copie estrutura/tipos/ganchos — não o domínio.
Princípio: a página depende do **contrato**, não do transporte. Hoje Inertia; amanhã pode ser API externa.

---

## Layout comutável (`.env` default + header `X-Layout`)

Permite rodar um layout experimental para QA enquanto a produção segue no default — global pelo `.env` ou
pontual pelo header.

**Backend — compartilhar o layout** (`app/Http/Middleware/HandleInertiaRequests.php`):
```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'layout' => $this->resolveLayout($request),
    ];
}

private function resolveLayout(Request $request): string
{
    $allowed = ['layout01', 'layout02'];
    $layout = $request->header('X-Layout') ?? config('app.layout');

    return in_array($layout, $allowed, true) ? $layout : 'layout01';
}
```
> Persistência: se quiser que o layout escolhido por header sobreviva à navegação SPA (que pode não reenviar o
> header), grave-o em sessão/cookie quando o header vier e use isso no fallback.

**Config** (`config/app.php`): `'layout' => env('APP_LAYOUT', 'layout01'),` — e no `.env`: `APP_LAYOUT=layout01`.

Cada layout é uma **pasta self-contained no topo de `resources/js/`** (`layout01`, `layout02`, …): entrada
`index.tsx` + todas as partes do layout dentro dela. Numerar facilita criar variantes em paralelo e **extrair
uma pasta inteira** quando o front virar projeto separado. O `layout01` é a baseline e re-exporta o `AppLayout`
do kit, sem mover nem editar o kit:
```
resources/js/
  layout01/
    index.tsx          ← baseline: re-exporta o AppLayout do kit (kit intacto)
  layout02/
    index.tsx          ← experimental, self-contained (todas as partes aqui)
  layouts/             ← infra do switch + kit existente (intactos)
    registry.ts
    resolved-app-layout.tsx
    app-layout.tsx, app/, auth/, settings/
```

**Registry** (`resources/js/layouts/registry.ts`) — chave = nome da pasta; variantes são **drop-in** (mesmas props):
```ts
import type { ComponentProps, ComponentType } from 'react';
import Layout01 from '@/layout01'; // baseline = kit (re-export)
import Layout02 from '@/layout02';

export type AppLayoutProps = ComponentProps<typeof Layout01>;

export const layoutRegistry = {
    layout01: Layout01,
    layout02: Layout02,
} satisfies Record<string, ComponentType<AppLayoutProps>>;

export type LayoutName = keyof typeof layoutRegistry; // 'layout01' | 'layout02'
```

**`resources/js/layout01/index.tsx`** — embrulha o kit sem tocá-lo:
```tsx
export { default } from '@/layouts/app-layout'; // baseline = layout do kit, intacto
```

**`resources/js/layout02/index.tsx`** — self-contained; aceita as mesmas props (drop-in):
```tsx
import type { ComponentProps } from 'react';
import type Layout01 from '@/layout01';

type Props = ComponentProps<typeof Layout01>; // mesmas props (breadcrumbs, children)

export default function Layout02({ children }: Props) {
    return <div className="min-h-screen">{children}</div>;
}
```

**Frontend — resolver** (`resources/js/layouts/resolved-app-layout.tsx`) — lê a prop e **repassa as props**
(ex.: `breadcrumbs`) ao layout escolhido; fallback no default:
```tsx
import { usePage } from '@inertiajs/react';
import { layoutRegistry, type AppLayoutProps } from './registry';

export default function ResolvedAppLayout(props: AppLayoutProps) {
    const { layout } = usePage().props;
    const Layout = layoutRegistry[layout] ?? layoutRegistry.layout01;

    return <Layout {...props} />;
}
```

**Tipar a prop** no `sharedPageProps` já existente (`resources/js/types/global.d.ts`) — sem interface nova:
```ts
import type { Auth } from '@/types/auth';
import type { LayoutName } from '@/layouts/registry';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            layout: LayoutName; // ← novo
            [key: string]: unknown;
        };
    }
}
```

**Ligar no `app.tsx`** — troca de **uma linha**; o `AppLayout` do kit só passa a ser embrulhado:
```ts
import ResolvedAppLayout from '@/layouts/resolved-app-layout';
// ...
default:
    return ResolvedAppLayout; // antes: AppLayout
// settings/, se quiser variar também: [ResolvedAppLayout, SettingsLayout]
```

Trocar de layout = `APP_LAYOUT` no `.env` (global) **ou** `X-Layout: layout02` na request (pontual, p/ teste).
**Sem env e sem header → render idêntico ao de hoje** (`layout01` = AppLayout do kit, não editado).

---

## Página consumindo o contrato

**Tipos** (`resources/js/types/transactions.ts`) — espelham o contrato 1:1:
```ts
export interface Transaction {
    id: number;
    amount: string;      // já formatado pelo backend (Money::format)
    description: string;
}
```

**Página** (`resources/js/pages/Transactions/Index.tsx`) — props via `usePage`, form via `useForm`,
rota via Wayfinder. **O layout é aplicado globalmente pelo `app.tsx`** — a página não define `Page.layout`:
```tsx
import { usePage, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { store } from '@/routes/transactions'; // helper gerado pelo Wayfinder (rota tipada)
import type { Transaction } from '@/types/transactions';

interface PageProps {
    transactions: Transaction[];
    [key: string]: unknown;
}

export default function Index() {
    const { transactions } = usePage<PageProps>().props;
    const form = useForm({ amount_cents: 0, currency: 'BRL', description: '', occurred_at: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(store().url, { preserveScroll: true });
    };

    return (
        <div>
            <form onSubmit={submit}>
                {/* inputs shadcn/ui; form.errors.* traz os 422 do contrato */}
                <button disabled={form.processing}>Salvar</button>
            </form>

            {transactions.length === 0
                ? <p>Nenhuma transação ainda.</p>
                : <ul>{transactions.map((t) => <li key={t.id}>{t.description} — {t.amount}</li>)}</ul>}
        </div>
    );
}
```

- Erros: `422` → `form.errors`; `403`/`404` → tela/toast de "sem acesso / não encontrado".
- Sem URL hardcoded: sempre o helper do Wayfinder (`./vendor/bin/sail npm run dev` regenera as rotas).
- A página não sabe se o dado veio de Inertia ou de uma API — consome só o **tipo do contrato** (extração futura barata).

---

## Garantir o multi-layout

Determinístico (mecanismo, não promessa):
- **Allowlist** no `resolveLayout` → header/env inválido cai no `default` (anti-injeção).
- **Tipos:** `LayoutName = keyof typeof layoutRegistry` + `satisfies Record<…, ComponentType<AppLayoutProps>>`
  → variante fora do registry ou com props incompatíveis **não passa** no `npm run types:check`.
- **Fallback gracioso:** `layoutRegistry[layout] ?? layout01` → nome no backend sem componente no front **degrada**
  para o `layout01`, nunca quebra.
- **Feature test** (Pest) trava o switch e o fallback:
```php
use Inertia\Testing\AssertableInertia as Assert;

it('usa o layout01 (baseline) sem header', function () {
    $this->actingAs(\App\Models\User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn (Assert $p) => $p->where('layout', 'layout01'));
});

it('troca pelo header X-Layout válido', function () {
    $this->actingAs(\App\Models\User::factory()->create())
        ->withHeader('X-Layout', 'layout02')
        ->get('/dashboard')
        ->assertInertia(fn (Assert $p) => $p->where('layout', 'layout02'));
});

it('cai no layout01 com layout inválido', function () {
    $this->actingAs(\App\Models\User::factory()->create())
        ->withHeader('X-Layout', 'xxx')
        ->get('/dashboard')
        ->assertInertia(fn (Assert $p) => $p->where('layout', 'layout01'));
});
```

Não automatizável (decisão de design): **stickiness** entre navegações SPA (persistir em cookie/sessão, se quiser)
e **cache/proxy** por URL ignorando o header (`Vary`/no-store em produção).
