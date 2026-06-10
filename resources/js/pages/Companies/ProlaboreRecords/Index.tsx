import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import InputError from '@/components/input-error';
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
import companies from '@/routes/companies';
import type {
    Company,
    CompanyPartner,
    ProlaboreRecord,
} from '@/types/companies';

interface PageProps {
    company: Pick<Company, 'id'>;
    records: ProlaboreRecord[];
    partners: CompanyPartner[];
    [key: string]: unknown;
}

interface CreateForm {
    partner_id: string;
    competencia: string;
    valor: string;
    observacao: string;
}

interface EditForm {
    competencia: string;
    valor: string;
    observacao: string;
}

function partnerName(partners: CompanyPartner[], partnerId: number): string {
    return (
        partners.find((p) => p.id === partnerId)?.nome ?? `Sócio #${partnerId}`
    );
}

function formatCompetencia(date: string): string {
    const [year, month] = date.split('-');
    return `${month}/${year}`;
}

export default function ProlaboreRecordsIndex({
    company,
    records,
    partners,
}: PageProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editingRecord, setEditingRecord] = useState<ProlaboreRecord | null>(
        null,
    );
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [filter, setFilter] = useState('');

    const createForm = useForm<CreateForm>({
        partner_id: '',
        competencia: '',
        valor: '',
        observacao: '',
    });

    const editForm = useForm<EditForm>({
        competencia: '',
        valor: '',
        observacao: '',
    });

    const handleCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post(
            companies.prolaboreRecords.store({ company: company.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCreateOpen(false);
                    createForm.reset();
                },
            },
        );
    };

    const openEdit = (record: ProlaboreRecord) => {
        editForm.setData({
            competencia: record.competencia.slice(0, 7),
            valor: String(record.valor),
            observacao: record.observacao ?? '',
        });
        setEditingRecord(record);
    };

    const handleEdit = (e: FormEvent) => {
        e.preventDefault();
        if (!editingRecord) return;
        editForm.put(
            companies.prolaboreRecords.update({
                company: company.id,
                record: editingRecord.id,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setEditingRecord(null),
            },
        );
    };

    const handleDelete = (recordId: number) => {
        router.delete(
            companies.prolaboreRecords.destroy({
                company: company.id,
                record: recordId,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setDeletingId(null),
            },
        );
    };

    const filteredRecords = filter
        ? records.filter((r) => r.competencia.startsWith(filter))
        : records;

    return (
        <>
            <Head title="Recibos de Pró-labore" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <Button asChild variant="ghost" size="sm" className="-ml-2">
                        <Link
                            href={companies.show({ company: company.id }).url}
                        >
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            Empresa
                        </Link>
                    </Button>

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button disabled={partners.length === 0}>
                                Novo Recibo
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    Registrar Recibo de Pró-labore
                                </DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleCreate} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label>Sócio</Label>
                                    <Select
                                        value={createForm.data.partner_id}
                                        onValueChange={(v) =>
                                            createForm.setData('partner_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione o sócio" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {partners.map((p) => (
                                                <SelectItem
                                                    key={p.id}
                                                    value={String(p.id)}
                                                >
                                                    {p.nome}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={createForm.errors.partner_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="create-competencia">
                                        Competência (MM/AAAA)
                                    </Label>
                                    <Input
                                        id="create-competencia"
                                        type="month"
                                        value={createForm.data.competencia}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'competencia',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={createForm.errors.competencia}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="create-valor">
                                        Valor (R$)
                                    </Label>
                                    <Input
                                        id="create-valor"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={createForm.data.valor}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'valor',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="3000.00"
                                    />
                                    <InputError
                                        message={createForm.errors.valor}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="create-obs">
                                        Observação (opcional)
                                    </Label>
                                    <Input
                                        id="create-obs"
                                        value={createForm.data.observacao}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'observacao',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="..."
                                    />
                                    <InputError
                                        message={createForm.errors.observacao}
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
                                        Registrar
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="flex items-end justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Recibos de Pró-labore
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Histórico de recibos mensais
                        </p>
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="filter-competencia" className="text-xs">
                            Filtrar por competência
                        </Label>
                        <Input
                            id="filter-competencia"
                            type="month"
                            value={filter}
                            onChange={(e) => setFilter(e.target.value)}
                            className="w-40"
                        />
                    </div>
                </div>

                {filteredRecords.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-12 text-center">
                        <p className="font-medium">Nenhum recibo encontrado</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {partners.length === 0
                                ? 'Cadastre sócios primeiro para registrar recibos.'
                                : filter
                                  ? 'Sem recibos para o período selecionado.'
                                  : 'Registre o primeiro recibo de pró-labore.'}
                        </p>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-3 text-left font-medium">
                                        Competência
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Sócio
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Valor
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Observação
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredRecords.map((record) => (
                                    <tr
                                        key={record.id}
                                        className="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {formatCompetencia(
                                                record.competencia,
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {partnerName(
                                                partners,
                                                record.partner_id,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {record.valor.toLocaleString(
                                                'pt-BR',
                                                {
                                                    style: 'currency',
                                                    currency: 'BRL',
                                                },
                                            )}
                                        </td>
                                        <td className="max-w-[200px] truncate px-4 py-3 text-muted-foreground">
                                            {record.observacao ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Dialog
                                                    open={
                                                        editingRecord?.id ===
                                                        record.id
                                                    }
                                                    onOpenChange={(open) => {
                                                        if (open)
                                                            openEdit(record);
                                                        else
                                                            setEditingRecord(
                                                                null,
                                                            );
                                                    }}
                                                >
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>
                                                                Editar Recibo
                                                            </DialogTitle>
                                                        </DialogHeader>
                                                        <form
                                                            onSubmit={
                                                                handleEdit
                                                            }
                                                            className="space-y-4"
                                                        >
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="edit-competencia">
                                                                    Competência
                                                                </Label>
                                                                <Input
                                                                    id="edit-competencia"
                                                                    type="month"
                                                                    value={
                                                                        editForm
                                                                            .data
                                                                            .competencia
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        editForm.setData(
                                                                            'competencia',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        editForm
                                                                            .errors
                                                                            .competencia
                                                                    }
                                                                />
                                                            </div>
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="edit-valor">
                                                                    Valor (R$)
                                                                </Label>
                                                                <Input
                                                                    id="edit-valor"
                                                                    type="number"
                                                                    min="0.01"
                                                                    step="0.01"
                                                                    value={
                                                                        editForm
                                                                            .data
                                                                            .valor
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        editForm.setData(
                                                                            'valor',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        editForm
                                                                            .errors
                                                                            .valor
                                                                    }
                                                                />
                                                            </div>
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="edit-obs">
                                                                    Observação
                                                                </Label>
                                                                <Input
                                                                    id="edit-obs"
                                                                    value={
                                                                        editForm
                                                                            .data
                                                                            .observacao
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        editForm.setData(
                                                                            'observacao',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        editForm
                                                                            .errors
                                                                            .observacao
                                                                    }
                                                                />
                                                            </div>
                                                            <DialogFooter>
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    onClick={() =>
                                                                        setEditingRecord(
                                                                            null,
                                                                        )
                                                                    }
                                                                >
                                                                    Cancelar
                                                                </Button>
                                                                <Button
                                                                    type="submit"
                                                                    disabled={
                                                                        editForm.processing
                                                                    }
                                                                >
                                                                    Salvar
                                                                </Button>
                                                            </DialogFooter>
                                                        </form>
                                                    </DialogContent>
                                                </Dialog>

                                                <Dialog
                                                    open={
                                                        deletingId === record.id
                                                    }
                                                    onOpenChange={(open) =>
                                                        setDeletingId(
                                                            open
                                                                ? record.id
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
                                                                Excluir recibo?
                                                            </DialogTitle>
                                                        </DialogHeader>
                                                        <p className="text-sm text-muted-foreground">
                                                            O recibo de{' '}
                                                            <strong>
                                                                {formatCompetencia(
                                                                    record.competencia,
                                                                )}
                                                            </strong>{' '}
                                                            de{' '}
                                                            <strong>
                                                                {partnerName(
                                                                    partners,
                                                                    record.partner_id,
                                                                )}
                                                            </strong>{' '}
                                                            será excluído.
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
                                                                        record.id,
                                                                    )
                                                                }
                                                            >
                                                                Excluir
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

ProlaboreRecordsIndex.layout = ({
    company,
}: {
    company: Pick<Company, 'id'>;
    [key: string]: unknown;
}) => ({
    breadcrumbs: [
        { title: 'Empresas', href: companies.index().url },
        {
            title: 'Empresa',
            href: companies.show({ company: company?.id ?? 0 }).url,
        },
        {
            title: 'Recibos',
            href: companies.prolaboreRecords.index({
                company: company?.id ?? 0,
            }).url,
        },
    ],
});
