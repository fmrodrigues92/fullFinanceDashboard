import { Head, Link, useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Building2, Plus, Trash2 } from 'lucide-react';
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
import type { Company, RegimeTributario } from '@/types/companies';
import { REGIME_OPTIONS } from '@/types/companies';

interface PageProps {
    companies: Company[];
    [key: string]: unknown;
}

interface CreateForm {
    razao_social: string;
    nome_fantasia: string;
    cnpj: string;
    regime_tributario: RegimeTributario | '';
}

export default function Index({ companies: list }: PageProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const form = useForm<CreateForm>({
        razao_social: '',
        nome_fantasia: '',
        cnpj: '',
        regime_tributario: '',
    });

    const handleCreate = (e: FormEvent) => {
        e.preventDefault();
        form.post(companies.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                setCreateOpen(false);
                form.reset();
            },
        });
    };

    const handleDelete = (id: number) => {
        router.delete(companies.destroy({ company: id }).url, {
            preserveScroll: true,
            onSuccess: () => setDeletingId(null),
        });
    };

    return (
        <>
            <Head title="Empresas" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Empresas</h1>
                        <p className="text-sm text-muted-foreground">
                            Gerencie suas empresas e sócios
                        </p>
                    </div>

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Nova Empresa
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Nova Empresa</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleCreate} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="razao_social">
                                        Razão Social
                                    </Label>
                                    <Input
                                        id="razao_social"
                                        value={form.data.razao_social}
                                        onChange={(e) =>
                                            form.setData(
                                                'razao_social',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Empresa Ltda"
                                    />
                                    <InputError
                                        message={form.errors.razao_social}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="nome_fantasia">
                                        Nome Fantasia
                                    </Label>
                                    <Input
                                        id="nome_fantasia"
                                        value={form.data.nome_fantasia}
                                        onChange={(e) =>
                                            form.setData(
                                                'nome_fantasia',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Empresa"
                                    />
                                    <InputError
                                        message={form.errors.nome_fantasia}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="cnpj">CNPJ</Label>
                                    <Input
                                        id="cnpj"
                                        value={form.data.cnpj}
                                        onChange={(e) =>
                                            form.setData('cnpj', e.target.value)
                                        }
                                        placeholder="00.000.000/0000-00"
                                    />
                                    <InputError message={form.errors.cnpj} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Regime Tributário</Label>
                                    <Select
                                        value={form.data.regime_tributario}
                                        onValueChange={(v) =>
                                            form.setData(
                                                'regime_tributario',
                                                v as RegimeTributario,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione o regime" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {REGIME_OPTIONS.map((opt) => (
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
                                        message={form.errors.regime_tributario}
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
                                        disabled={form.processing}
                                    >
                                        Criar
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {list.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-12 text-center">
                        <Building2 className="h-10 w-10 text-muted-foreground" />
                        <div>
                            <p className="font-medium">
                                Nenhuma empresa cadastrada
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Crie sua primeira empresa para começar.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-3 text-left font-medium">
                                        Razão Social
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        CNPJ
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Regime
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {list.map((company) => (
                                    <tr
                                        key={company.id}
                                        className="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3">
                                            <Link
                                                href={
                                                    companies.show({
                                                        company: company.id,
                                                    }).url
                                                }
                                                className="font-medium hover:underline"
                                            >
                                                {company.razao_social}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {company.nome_fantasia}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {company.cnpj_formatted}
                                        </td>
                                        <td className="px-4 py-3">
                                            {company.regime_tributario_label}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={
                                                            companies.show({
                                                                company:
                                                                    company.id,
                                                            }).url
                                                        }
                                                    >
                                                        Ver
                                                    </Link>
                                                </Button>

                                                <Dialog
                                                    open={
                                                        deletingId ===
                                                        company.id
                                                    }
                                                    onOpenChange={(open) =>
                                                        setDeletingId(
                                                            open
                                                                ? company.id
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
                                                                Excluir empresa?
                                                            </DialogTitle>
                                                        </DialogHeader>
                                                        <p className="text-sm text-muted-foreground">
                                                            Esta ação removerá{' '}
                                                            <strong>
                                                                {
                                                                    company.razao_social
                                                                }
                                                            </strong>{' '}
                                                            e todos os seus
                                                            sócios,
                                                            configurações e
                                                            recibos. Não pode
                                                            ser desfeita.
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
                                                                        company.id,
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

Index.layout = {
    breadcrumbs: [{ title: 'Empresas', href: companies.index().url }],
};
