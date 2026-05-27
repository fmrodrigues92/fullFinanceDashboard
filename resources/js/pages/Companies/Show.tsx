import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ChevronRight, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
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
import { Separator } from '@/components/ui/separator';
import companies from '@/routes/companies';
import type {
    Company,
    CompanyPartner,
    PartnerData,
    RegimeTributario,
} from '@/types/companies';
import { REGIME_OPTIONS } from '@/types/companies';

interface PageProps {
    company: Company;
    partners: CompanyPartner[];
    [key: string]: unknown;
}

interface EditForm {
    razao_social: string;
    nome_fantasia: string;
    cnpj: string;
    regime_tributario: RegimeTributario | '';
}

export default function Show({ company, partners }: PageProps) {
    const [editOpen, setEditOpen] = useState(false);
    const [syncErrors, setSyncErrors] = useState<string | null>(null);
    const [syncProcessing, setSyncProcessing] = useState(false);
    const [localPartners, setLocalPartners] = useState<PartnerData[]>(() =>
        partners.map((p) => ({
            nome: p.nome,
            cpf: p.cpf,
            participacao: p.participacao,
        })),
    );

    useEffect(() => {
        setLocalPartners(
            partners.map((p) => ({
                nome: p.nome,
                cpf: p.cpf,
                participacao: p.participacao,
            })),
        );
    }, [partners]);

    const editForm = useForm<EditForm>({
        razao_social: company.razao_social,
        nome_fantasia: company.nome_fantasia,
        cnpj: company.cnpj_formatted,
        regime_tributario: company.regime_tributario,
    });

    const handleEdit = (e: FormEvent) => {
        e.preventDefault();
        editForm.put(companies.update({ company: company.id }).url, {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    };

    const openEdit = () => {
        editForm.setData({
            razao_social: company.razao_social,
            nome_fantasia: company.nome_fantasia,
            cnpj: company.cnpj_formatted,
            regime_tributario: company.regime_tributario,
        });
        setEditOpen(true);
    };

    const addPartner = () => {
        setLocalPartners((prev) => [
            ...prev,
            { nome: '', cpf: '', participacao: 0 },
        ]);
    };

    const removePartner = (index: number) => {
        setLocalPartners((prev) => prev.filter((_, i) => i !== index));
    };

    const updatePartner = (
        index: number,
        field: keyof PartnerData,
        value: string | number,
    ) => {
        setLocalPartners((prev) =>
            prev.map((p, i) => (i === index ? { ...p, [field]: value } : p)),
        );
    };

    const handleSyncPartners = () => {
        setSyncProcessing(true);
        setSyncErrors(null);
        router.put(
            companies.partners.sync({ company: company.id }).url,
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            { partners: localPartners as any[] },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setSyncErrors(
                        (errors.partners as string) ??
                            Object.values(errors).join(', '),
                    );
                    setSyncProcessing(false);
                },
                onSuccess: () => setSyncProcessing(false),
            },
        );
    };

    const totalParticipacao = localPartners.reduce(
        (sum, p) => sum + Number(p.participacao || 0),
        0,
    );

    return (
        <>
            <Head title={company.razao_social} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center gap-2">
                    <Button asChild variant="ghost" size="sm" className="-ml-2">
                        <Link href={companies.index().url}>
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            Empresas
                        </Link>
                    </Button>
                </div>

                {/* Company Info */}
                <div className="rounded-xl border p-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-xl font-semibold">
                                {company.razao_social}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {company.nome_fantasia}
                            </p>
                        </div>
                        <Dialog open={editOpen} onOpenChange={setEditOpen}>
                            <DialogTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={openEdit}
                                >
                                    Editar
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Editar Empresa</DialogTitle>
                                </DialogHeader>
                                <form
                                    onSubmit={handleEdit}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-razao">
                                            Razão Social
                                        </Label>
                                        <Input
                                            id="edit-razao"
                                            value={editForm.data.razao_social}
                                            onChange={(e) =>
                                                editForm.setData(
                                                    'razao_social',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                editForm.errors.razao_social
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-fantasia">
                                            Nome Fantasia
                                        </Label>
                                        <Input
                                            id="edit-fantasia"
                                            value={editForm.data.nome_fantasia}
                                            onChange={(e) =>
                                                editForm.setData(
                                                    'nome_fantasia',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                editForm.errors.nome_fantasia
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-cnpj">CNPJ</Label>
                                        <Input
                                            id="edit-cnpj"
                                            value={editForm.data.cnpj}
                                            onChange={(e) =>
                                                editForm.setData(
                                                    'cnpj',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={editForm.errors.cnpj}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Regime Tributário</Label>
                                        <Select
                                            value={
                                                editForm.data.regime_tributario
                                            }
                                            onValueChange={(v) =>
                                                editForm.setData(
                                                    'regime_tributario',
                                                    v as RegimeTributario,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
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
                                            message={
                                                editForm.errors
                                                    .regime_tributario
                                            }
                                        />
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setEditOpen(false)}
                                        >
                                            Cancelar
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={editForm.processing}
                                        >
                                            Salvar
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <Separator className="my-4" />

                    <dl className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt className="text-muted-foreground">CNPJ</dt>
                            <dd className="font-mono font-medium">
                                {company.cnpj_formatted}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Regime Tributário
                            </dt>
                            <dd className="font-medium">
                                {company.regime_tributario_label}
                            </dd>
                        </div>
                    </dl>
                </div>

                {/* Partners */}
                <div className="rounded-xl border p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Sócios</h2>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={addPartner}
                        >
                            <Plus className="mr-1 h-4 w-4" />
                            Adicionar Sócio
                        </Button>
                    </div>

                    {localPartners.length === 0 ? (
                        <p className="py-4 text-center text-sm text-muted-foreground">
                            Nenhum sócio cadastrado.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {localPartners.map((partner, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-[1fr_1fr_120px_36px] items-start gap-2"
                                >
                                    <div className="grid gap-1">
                                        {index === 0 && (
                                            <Label className="text-xs text-muted-foreground">
                                                Nome
                                            </Label>
                                        )}
                                        <Input
                                            value={partner.nome}
                                            onChange={(e) =>
                                                updatePartner(
                                                    index,
                                                    'nome',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Nome completo"
                                        />
                                    </div>
                                    <div className="grid gap-1">
                                        {index === 0 && (
                                            <Label className="text-xs text-muted-foreground">
                                                CPF
                                            </Label>
                                        )}
                                        <Input
                                            value={partner.cpf}
                                            onChange={(e) =>
                                                updatePartner(
                                                    index,
                                                    'cpf',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="000.000.000-00"
                                        />
                                    </div>
                                    <div className="grid gap-1">
                                        {index === 0 && (
                                            <Label className="text-xs text-muted-foreground">
                                                Participação (%)
                                            </Label>
                                        )}
                                        <Input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            value={partner.participacao}
                                            onChange={(e) =>
                                                updatePartner(
                                                    index,
                                                    'participacao',
                                                    parseFloat(
                                                        e.target.value,
                                                    ) || 0,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className={index === 0 ? 'mt-6' : ''}>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => removePartner(index)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="mt-4 flex items-center justify-between">
                        <p
                            className={`text-sm ${totalParticipacao !== 100 && localPartners.length > 0 ? 'text-destructive' : 'text-muted-foreground'}`}
                        >
                            Total:{' '}
                            <strong>{totalParticipacao.toFixed(2)}%</strong>
                            {localPartners.length > 0 &&
                                totalParticipacao !== 100 && (
                                    <span className="ml-2">
                                        (deve ser 100%)
                                    </span>
                                )}
                        </p>
                        <div className="flex flex-col items-end gap-1">
                            {syncErrors && (
                                <p className="text-xs text-destructive">
                                    {syncErrors}
                                </p>
                            )}
                            <Button
                                onClick={handleSyncPartners}
                                disabled={syncProcessing}
                                size="sm"
                            >
                                Salvar Sócios
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Quick links */}
                <div className="grid gap-3 sm:grid-cols-2">
                    <Link
                        href={
                            companies.prolaboreConfigs.index({
                                company: company.id,
                            }).url
                        }
                        className="flex items-center justify-between rounded-xl border p-4 hover:bg-muted/50"
                    >
                        <div>
                            <p className="font-medium">
                                Configuração de Pró-labore
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Defina os valores mensais por sócio
                            </p>
                        </div>
                        <ChevronRight className="h-5 w-5 text-muted-foreground" />
                    </Link>

                    <Link
                        href={
                            companies.prolaboreRecords.index({
                                company: company.id,
                            }).url
                        }
                        className="flex items-center justify-between rounded-xl border p-4 hover:bg-muted/50"
                    >
                        <div>
                            <p className="font-medium">Recibos de Pró-labore</p>
                            <p className="text-sm text-muted-foreground">
                                Registre os recibos mensais
                            </p>
                        </div>
                        <ChevronRight className="h-5 w-5 text-muted-foreground" />
                    </Link>
                </div>
            </div>
        </>
    );
}

Show.layout = ({ company }: { company: Company; [key: string]: unknown }) => ({
    breadcrumbs: [
        { title: 'Empresas', href: companies.index().url },
        {
            title: company?.razao_social ?? 'Empresa',
            href: companies.show({ company: company?.id ?? 0 }).url,
        },
    ],
});
