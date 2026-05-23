# Padrões de código — Frontend

Referência canônica de **forma**. Copie estrutura/tipos/ganchos — não o domínio.
A página depende do **contrato** (props Inertia tipadas), não inventa a API.

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
rota via Wayfinder:
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
