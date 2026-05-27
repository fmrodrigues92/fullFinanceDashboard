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
    ProlaboreConfig,
} from '@/types/companies';

interface PageProps {
    company: Pick<Company, 'id'>;
    configs: ProlaboreConfig[];
    partners: CompanyPartner[];
    [key: string]: unknown;
}

interface CreateForm {
    partner_id: string;
    valor: string;
}

interface EditForm {
    valor: string;
}

function partnerName(partners: CompanyPartner[], partnerId: number): string {
    return (
        partners.find((p) => p.id === partnerId)?.nome ?? `Sócio #${partnerId}`
    );
}

export default function ProlaboreConfigsIndex({
    company,
    configs,
    partners,
}: PageProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editingConfig, setEditingConfig] = useState<ProlaboreConfig | null>(
        null,
    );
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const createForm = useForm<CreateForm>({ partner_id: '', valor: '' });
    const editForm = useForm<EditForm>({ valor: '' });

    const handleCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post(
            companies.prolaboreConfigs.store({ company: company.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCreateOpen(false);
                    createForm.reset();
                },
            },
        );
    };

    const openEdit = (config: ProlaboreConfig) => {
        editForm.setData({ valor: String(config.valor) });
        setEditingConfig(config);
    };

    const handleEdit = (e: FormEvent) => {
        e.preventDefault();
        if (!editingConfig) return;
        editForm.put(
            companies.prolaboreConfigs.update({
                company: company.id,
                config: editingConfig.id,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setEditingConfig(null),
            },
        );
    };

    const handleDelete = (configId: number) => {
        router.delete(
            companies.prolaboreConfigs.destroy({
                company: company.id,
                config: configId,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setDeletingId(null),
            },
        );
    };

    const partnersWithoutConfig = partners.filter(
        (p) => !configs.some((c) => c.partner_id === p.id),
    );

    return (
        <>
            <Head title="Configuração de Pró-labore" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="-ml-2"
                        >
                            <Link
                                href={
                                    companies.show({ company: company.id }).url
                                }
                            >
                                <ArrowLeft className="mr-1 h-4 w-4" />
                                Empresa
                            </Link>
                        </Button>
                    </div>

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button
                                disabled={partnersWithoutConfig.length === 0}
                            >
                                Nova Configuração
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    Nova Configuração de Pró-labore
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
                                            {partnersWithoutConfig.map((p) => (
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

                <div>
                    <h1 className="text-2xl font-semibold">
                        Configuração de Pró-labore
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Valor mensal de pró-labore por sócio
                    </p>
                </div>

                {configs.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-12 text-center">
                        <p className="font-medium">
                            Nenhuma configuração cadastrada
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {partners.length === 0
                                ? 'Cadastre sócios primeiro para configurar o pró-labore.'
                                : 'Crie a configuração de pró-labore para cada sócio.'}
                        </p>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-3 text-left font-medium">
                                        Sócio
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Valor Mensal
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {configs.map((config) => (
                                    <tr
                                        key={config.id}
                                        className="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {partnerName(
                                                partners,
                                                config.partner_id,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {config.valor.toLocaleString(
                                                'pt-BR',
                                                {
                                                    style: 'currency',
                                                    currency: 'BRL',
                                                },
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Dialog
                                                    open={
                                                        editingConfig?.id ===
                                                        config.id
                                                    }
                                                    onOpenChange={(open) => {
                                                        if (open)
                                                            openEdit(config);
                                                        else
                                                            setEditingConfig(
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
                                                                Editar
                                                                Pró-labore —{' '}
                                                                {partnerName(
                                                                    partners,
                                                                    config.partner_id,
                                                                )}
                                                            </DialogTitle>
                                                        </DialogHeader>
                                                        <form
                                                            onSubmit={
                                                                handleEdit
                                                            }
                                                            className="space-y-4"
                                                        >
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
                                                            <DialogFooter>
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    onClick={() =>
                                                                        setEditingConfig(
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
                                                        deletingId === config.id
                                                    }
                                                    onOpenChange={(open) =>
                                                        setDeletingId(
                                                            open
                                                                ? config.id
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
                                                                Remover
                                                                configuração?
                                                            </DialogTitle>
                                                        </DialogHeader>
                                                        <p className="text-sm text-muted-foreground">
                                                            A configuração de
                                                            pró-labore de{' '}
                                                            <strong>
                                                                {partnerName(
                                                                    partners,
                                                                    config.partner_id,
                                                                )}
                                                            </strong>{' '}
                                                            será removida.
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
                                                                        config.id,
                                                                    )
                                                                }
                                                            >
                                                                Remover
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

ProlaboreConfigsIndex.layout = ({
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
            title: 'Pró-labore Config',
            href: companies.prolaboreConfigs.index({
                company: company?.id ?? 0,
            }).url,
        },
    ],
});
