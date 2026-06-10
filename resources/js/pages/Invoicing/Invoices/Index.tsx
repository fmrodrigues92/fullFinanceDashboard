import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ChevronRight, Trash2 } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { PaginationNav } from '@/components/ui/pagination-nav';
import companies from '@/routes/companies';
import type {
    ClientOption,
    Invoice,
    InvoiceTipo,
    PaginationMeta,
} from '@/types/invoicing';
import { ANEXO_OPTIONS, TIPO_OPTIONS } from '@/types/invoicing';

interface ActiveFilters {
    is_simulation: string;
    competencia: string;
    tipo: string;
    per_page: number;
}

interface PageProps {
    company: { id: number };
    invoices: Invoice[];
    pagination: PaginationMeta;
    clients: ClientOption[];
    filters: ActiveFilters;
    [key: string]: unknown;
}

interface CreateForm {
    client_id: string;
    tipo: InvoiceTipo | '';
    anexo_cnae: string;
    data_emissao: string;
    valor_brl: string;
    valor_usd: string;
    cotacao: string;
    observacao: string;
}

function clientName(clients: ClientOption[], id: number | null): string {
    if (id === null) return '—';
    return clients.find((c) => c.id === id)?.nome ?? `#${id}`;
}

function fmtBrl(value: string): string {
    return parseFloat(value).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

function simFromParam(v: string): 'all' | 'real' | 'simulation' {
    if (v === 'true') return 'simulation';
    if (v === 'false') return 'real';
    return 'all';
}

export default function InvoicesIndex({
    company,
    invoices,
    pagination,
    clients,
    filters,
}: PageProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const [filterSim, setFilterSim] = useState<'all' | 'real' | 'simulation'>(
        () => simFromParam(filters.is_simulation),
    );
    const [filterMonth, setFilterMonth] = useState(() => filters.competencia);

    useEffect(() => {
        setFilterSim(simFromParam(filters.is_simulation));
        setFilterMonth(filters.competencia);
    }, [filters.is_simulation, filters.competencia]);

    const createForm = useForm<CreateForm>({
        client_id: '',
        tipo: '',
        anexo_cnae: '',
        data_emissao: '',
        valor_brl: '',
        valor_usd: '',
        cotacao: '',
        observacao: '',
    });

    const isInternacional = createForm.data.tipo === 'internacional';

    const handleCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post(companies.invoices.store({ company: company.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                setCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const buildQuery = (
        overrides: Record<string, string> = {},
    ): Record<string, string> => {
        const query: Record<string, string> = {
            per_page: String(pagination.per_page),
        };
        if (filterSim === 'simulation') query.is_simulation = 'true';
        if (filterSim === 'real') query.is_simulation = 'false';
        if (filterMonth) query.competencia = filterMonth;
        return { ...query, ...overrides };
    };

    const goToPage = (page: number) => {
        router.get(
            companies.invoices.index({ company: company.id }).url,
            buildQuery({ page: String(page) }),
            { preserveScroll: true, preserveState: true },
        );
    };

    const changePerPage = (perPage: number) => {
        router.get(
            companies.invoices.index({ company: company.id }).url,
            buildQuery({ page: '1', per_page: String(perPage) }),
            { preserveScroll: true, preserveState: true },
        );
    };

    const handleDelete = (invoiceId: number) => {
        router.delete(
            companies.invoices.destroy({
                company: company.id,
                invoice: invoiceId,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setDeletingId(null),
            },
        );
    };

    const applyFilters = () => {
        router.get(
            companies.invoices.index({ company: company.id }).url,
            buildQuery({ page: '1' }),
            { preserveScroll: true, preserveState: true },
        );
    };

    const totalBrl = invoices.reduce(
        (sum, inv) => sum + parseFloat(inv.valor_brl),
        0,
    );

    return (
        <>
            <Head title="Notas Fiscais" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <Button asChild variant="ghost" size="sm" className="-ml-2">
                        <Link
                            href={companies.show({ company: company.id }).url}
                        >
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            Empresa
                        </Link>
                    </Button>

                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={
                                    companies.simulationBatches.index({
                                        company: company.id,
                                    }).url
                                }
                            >
                                Lotes de Simulação
                                <ChevronRight className="ml-1 h-4 w-4" />
                            </Link>
                        </Button>

                        <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                            <DialogTrigger asChild>
                                <Button>Nova Nota</Button>
                            </DialogTrigger>
                            <DialogContent className="max-w-lg">
                                <DialogHeader>
                                    <DialogTitle>Nova Nota Fiscal</DialogTitle>
                                </DialogHeader>
                                <form
                                    onSubmit={handleCreate}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label>Cliente</Label>
                                        <Select
                                            value={createForm.data.client_id}
                                            onValueChange={(v) =>
                                                createForm.setData(
                                                    'client_id',
                                                    v,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecione o cliente" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {clients.map((c) => (
                                                    <SelectItem
                                                        key={c.id}
                                                        value={String(c.id)}
                                                    >
                                                        {c.nome}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                createForm.errors.client_id
                                            }
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label>Tipo</Label>
                                            <Select
                                                value={createForm.data.tipo}
                                                onValueChange={(v) => {
                                                    createForm.setData(
                                                        'tipo',
                                                        v as InvoiceTipo,
                                                    );
                                                    if (v === 'nacional') {
                                                        createForm.setData(
                                                            'valor_usd',
                                                            '',
                                                        );
                                                        createForm.setData(
                                                            'cotacao',
                                                            '',
                                                        );
                                                    }
                                                }}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Tipo" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {TIPO_OPTIONS.map((o) => (
                                                        <SelectItem
                                                            key={o.value}
                                                            value={o.value}
                                                        >
                                                            {o.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={createForm.errors.tipo}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label>Anexo CNAE</Label>
                                            <Select
                                                value={
                                                    createForm.data.anexo_cnae
                                                }
                                                onValueChange={(v) =>
                                                    createForm.setData(
                                                        'anexo_cnae',
                                                        v,
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Anexo" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {ANEXO_OPTIONS.map((o) => (
                                                        <SelectItem
                                                            key={o.value}
                                                            value={o.value}
                                                        >
                                                            {o.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    createForm.errors.anexo_cnae
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="data-emissao">
                                                Data de Emissão
                                            </Label>
                                            <Input
                                                id="data-emissao"
                                                type="date"
                                                value={
                                                    createForm.data.data_emissao
                                                }
                                                onChange={(e) =>
                                                    createForm.setData(
                                                        'data_emissao',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    createForm.errors
                                                        .data_emissao
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="valor-brl">
                                                Valor (R$)
                                            </Label>
                                            <Input
                                                id="valor-brl"
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                value={
                                                    createForm.data.valor_brl
                                                }
                                                onChange={(e) =>
                                                    createForm.setData(
                                                        'valor_brl',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="10000.00"
                                            />
                                            <InputError
                                                message={
                                                    createForm.errors.valor_brl
                                                }
                                            />
                                        </div>
                                    </div>

                                    {isInternacional && (
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="valor-usd">
                                                    Valor (USD)
                                                </Label>
                                                <Input
                                                    id="valor-usd"
                                                    type="number"
                                                    min="0.01"
                                                    step="0.01"
                                                    value={
                                                        createForm.data
                                                            .valor_usd
                                                    }
                                                    onChange={(e) =>
                                                        createForm.setData(
                                                            'valor_usd',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="2000.00"
                                                />
                                                <InputError
                                                    message={
                                                        createForm.errors
                                                            .valor_usd
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="cotacao">
                                                    Cotação BRL/USD
                                                </Label>
                                                <Input
                                                    id="cotacao"
                                                    type="number"
                                                    min="0.0001"
                                                    step="0.0001"
                                                    value={
                                                        createForm.data.cotacao
                                                    }
                                                    onChange={(e) =>
                                                        createForm.setData(
                                                            'cotacao',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="5.0000"
                                                />
                                                <InputError
                                                    message={
                                                        createForm.errors
                                                            .cotacao
                                                    }
                                                />
                                            </div>
                                        </div>
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="observacao">
                                            Observação{' '}
                                            <span className="text-muted-foreground">
                                                (opcional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="observacao"
                                            value={createForm.data.observacao}
                                            onChange={(e) =>
                                                createForm.setData(
                                                    'observacao',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                createForm.errors.observacao
                                            }
                                        />
                                    </div>

                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setCreateOpen(false)}
                                        >
                                            Cancelar
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={createForm.processing}
                                        >
                                            Criar
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div>
                    <h1 className="text-2xl font-semibold">Notas Fiscais</h1>
                    <p className="text-sm text-muted-foreground">
                        Notas reais e simulações de faturamento
                    </p>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap items-end gap-3 rounded-xl border p-4">
                    <div className="grid gap-1">
                        <Label className="text-xs text-muted-foreground">
                            Tipo
                        </Label>
                        <Select
                            value={filterSim}
                            onValueChange={(v) =>
                                setFilterSim(v as 'all' | 'real' | 'simulation')
                            }
                        >
                            <SelectTrigger className="w-44">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="real">
                                    Somente reais
                                </SelectItem>
                                <SelectItem value="simulation">
                                    Somente simulações
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1">
                        <Label
                            htmlFor="filter-month"
                            className="text-xs text-muted-foreground"
                        >
                            Competência
                        </Label>
                        <Input
                            id="filter-month"
                            type="month"
                            className="w-40"
                            value={filterMonth}
                            onChange={(e) => setFilterMonth(e.target.value)}
                        />
                    </div>

                    <Button onClick={applyFilters} variant="secondary">
                        Filtrar
                    </Button>

                    {(filterSim !== 'all' || filterMonth) && (
                        <Button
                            variant="ghost"
                            onClick={() => {
                                router.get(
                                    companies.invoices.index({
                                        company: company.id,
                                    }).url,
                                    { per_page: String(pagination.per_page) },
                                    { preserveScroll: true },
                                );
                            }}
                        >
                            Limpar
                        </Button>
                    )}
                </div>

                {/* Summary */}
                {invoices.length > 0 && (
                    <div className="rounded-xl border bg-muted/30 px-5 py-3 text-sm">
                        <span className="text-muted-foreground">
                            {pagination.total} registro
                            {pagination.total !== 1 ? 's' : ''} ·{' '}
                        </span>
                        <span className="font-semibold">
                            Total:{' '}
                            {totalBrl.toLocaleString('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                            })}
                        </span>
                    </div>
                )}

                {/* Table */}
                {invoices.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-12 text-center">
                        <p className="font-medium">Nenhuma nota encontrada</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Crie uma nota fiscal ou um lote de simulações.
                        </p>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-3 text-left font-medium">
                                        Data
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Cliente
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Tipo / Anexo
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Valor BRL
                                    </th>
                                    <th className="px-4 py-3 text-center font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {invoices.map((inv) => (
                                    <tr
                                        key={inv.id}
                                        className="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {inv.data_emissao}
                                        </td>
                                        <td className="px-4 py-3">
                                            {clientName(clients, inv.client_id)}
                                        </td>
                                        <td className="px-4 py-3 capitalize">
                                            {inv.tipo} · Anexo {inv.anexo_cnae}
                                        </td>
                                        <td className="px-4 py-3 text-right font-medium">
                                            {fmtBrl(inv.valor_brl)}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            {inv.is_simulation ? (
                                                <Badge variant="secondary">
                                                    Simulação
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    Real
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {!inv.is_simulation && (
                                                <Dialog
                                                    open={deletingId === inv.id}
                                                    onOpenChange={(open) =>
                                                        setDeletingId(
                                                            open
                                                                ? inv.id
                                                                : null,
                                                        )
                                                    }
                                                >
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>
                                                                Excluir nota?
                                                            </DialogTitle>
                                                        </DialogHeader>
                                                        <p className="text-sm text-muted-foreground">
                                                            A nota de{' '}
                                                            <strong>
                                                                {fmtBrl(
                                                                    inv.valor_brl,
                                                                )}
                                                            </strong>{' '}
                                                            ({inv.data_emissao})
                                                            será excluída por
                                                            soft delete.
                                                        </p>
                                                        <DialogFooter>
                                                            <Button
                                                                variant="outline"
                                                                onClick={() =>
                                                                    setDeletingId(
                                                                        null,
                                                                    )
                                                                }
                                                            >
                                                                Cancelar
                                                            </Button>
                                                            <Button
                                                                variant="destructive"
                                                                onClick={() =>
                                                                    handleDelete(
                                                                        inv.id,
                                                                    )
                                                                }
                                                            >
                                                                Excluir
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <PaginationNav
                            pagination={pagination}
                            onPage={goToPage}
                            onPerPage={changePerPage}
                        />
                    </div>
                )}
            </div>
        </>
    );
}

InvoicesIndex.layout = ({
    company,
}: {
    company: { id: number };
    [key: string]: unknown;
}) => ({
    breadcrumbs: [
        { title: 'Empresas', href: companies.index().url },
        {
            title: 'Empresa',
            href: companies.show({ company: company?.id ?? 0 }).url,
        },
        {
            title: 'Notas Fiscais',
            href: companies.invoices.index({
                company: company?.id ?? 0,
            }).url,
        },
    ],
});
