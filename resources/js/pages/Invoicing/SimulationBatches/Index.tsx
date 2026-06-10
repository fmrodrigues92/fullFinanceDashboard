import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
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
import type { InvoiceTipo, SimulationBatch } from '@/types/invoicing';
import { ANEXO_OPTIONS, TIPO_OPTIONS } from '@/types/invoicing';

interface PageProps {
    company: { id: number };
    batches: SimulationBatch[];
    [key: string]: unknown;
}

interface CreateBatchForm {
    tipo: InvoiceTipo | '';
    anexo_cnae: string;
    data_inicio: string;
    data_termino: string;
    valor_brl: string;
}

export default function SimulationBatchesIndex({
    company,
    batches,
}: PageProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const createForm = useForm<CreateBatchForm>({
        tipo: '',
        anexo_cnae: '',
        data_inicio: '',
        data_termino: '',
        valor_brl: '',
    });

    const handleCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post(
            companies.simulationBatches.store({ company: company.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCreateOpen(false);
                    createForm.reset();
                },
            },
        );
    };

    const handleDelete = (batchId: number) => {
        router.delete(
            companies.simulationBatches.destroy({
                company: company.id,
                simulationBatch: batchId,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setDeletingId(null),
            },
        );
    };

    return (
        <>
            <Head title="Lotes de Simulação" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <Button asChild variant="ghost" size="sm" className="-ml-2">
                        <Link
                            href={
                                companies.invoices.index({
                                    company: company.id,
                                }).url
                            }
                        >
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            Notas Fiscais
                        </Link>
                    </Button>

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>Novo Lote</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    Novo Lote de Simulação
                                </DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleCreate} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label>Tipo</Label>
                                    <Select
                                        value={createForm.data.tipo}
                                        onValueChange={(v) =>
                                            createForm.setData(
                                                'tipo',
                                                v as InvoiceTipo,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione o tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {TIPO_OPTIONS.map((opt) => (
                                                <SelectItem
                                                    key={opt.value}
                                                    value={opt.value}
                                                >
                                                    {opt.label}
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
                                        value={createForm.data.anexo_cnae}
                                        onValueChange={(v) =>
                                            createForm.setData('anexo_cnae', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione o anexo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ANEXO_OPTIONS.map((opt) => (
                                                <SelectItem
                                                    key={opt.value}
                                                    value={opt.value}
                                                >
                                                    {opt.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={createForm.errors.anexo_cnae}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="data-inicio">
                                            Início
                                        </Label>
                                        <Input
                                            id="data-inicio"
                                            type="month"
                                            value={createForm.data.data_inicio}
                                            onChange={(e) =>
                                                createForm.setData(
                                                    'data_inicio',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                createForm.errors.data_inicio
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="data-termino">
                                            Término
                                        </Label>
                                        <Input
                                            id="data-termino"
                                            type="month"
                                            value={createForm.data.data_termino}
                                            onChange={(e) =>
                                                createForm.setData(
                                                    'data_termino',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                createForm.errors.data_termino
                                            }
                                        />
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="valor-brl">
                                        Valor (R$)
                                    </Label>
                                    <Input
                                        id="valor-brl"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="15000.00"
                                        value={createForm.data.valor_brl}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'valor_brl',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={createForm.errors.valor_brl}
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
                                        Criar Lote
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div>
                    <h1 className="text-2xl font-semibold">
                        Lotes de Simulação
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Simulações de faturamento por período
                    </p>
                </div>

                {batches.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-12 text-center">
                        <p className="font-medium">Nenhum lote cadastrado</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Crie um lote para simular faturamento em um período.
                        </p>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-3 text-left font-medium">
                                        Lote
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {batches.map((batch) => (
                                    <tr
                                        key={batch.id}
                                        className="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            Lote #{batch.id}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Dialog
                                                open={deletingId === batch.id}
                                                onOpenChange={(open) =>
                                                    setDeletingId(
                                                        open ? batch.id : null,
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
                                                            Remover lote?
                                                        </DialogTitle>
                                                    </DialogHeader>
                                                    <p className="text-sm text-muted-foreground">
                                                        <strong>
                                                            Lote #{batch.id}
                                                        </strong>{' '}
                                                        e todas as suas
                                                        simulações serão
                                                        permanentemente
                                                        removidos.
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
                                                                    batch.id,
                                                                )
                                                            }
                                                        >
                                                            Remover
                                                        </Button>
                                                    </DialogFooter>
                                                </DialogContent>
                                            </Dialog>
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

SimulationBatchesIndex.layout = ({
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
            href: companies.invoices.index({ company: company?.id ?? 0 }).url,
        },
        {
            title: 'Lotes de Simulação',
            href: companies.simulationBatches.index({
                company: company?.id ?? 0,
            }).url,
        },
    ],
});
