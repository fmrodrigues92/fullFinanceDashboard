import { useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, TrendingDown, TrendingUp } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import companies from '@/routes/companies';
import type {
    CreateProlaboreRecordPayload,
    FatorR,
    ProlaboreDashboardData,
    ProlaboreTipo,
    SocioProlaboreDashboard,
    UpdateProlaboreRecordPayload,
} from '@/types/dashboard';
import { brl } from '../lib/format';

interface Props {
    prolabore: ProlaboreDashboardData;
    companyId: number;
    isCurrentMonth: boolean;
}

// ─── Badge de tipo ────────────────────────────────────────────────────────────

const TIPO_CONFIG: Record<ProlaboreTipo, { label: string; className: string }> =
    {
        recibo_manual: {
            label: 'Lançado manualmente',
            className:
                'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        },
        recibo_automatico: {
            label: 'Gerado automaticamente',
            className:
                'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        },
        sem_recibo: {
            label: 'Sem recibo',
            className:
                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        },
        previsao: {
            label: 'Prévia',
            className: 'bg-muted text-muted-foreground',
        },
        sem_faturamento: {
            label: 'Sem faturamento',
            className: 'bg-muted text-muted-foreground',
        },
        sem_config: {
            label: 'Sem configuração',
            className:
                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        },
    };

function TipoBadge({ tipo }: { tipo: ProlaboreTipo }) {
    const { label, className } = TIPO_CONFIG[tipo];
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${className}`}
        >
            {label}
        </span>
    );
}

// ─── Badge Fator R ────────────────────────────────────────────────────────────

function FatorRBadge({ fatorR }: { fatorR: FatorR }) {
    const pct = (fatorR.percentual * 100).toFixed(1);
    const label = fatorR.dentro ? `Fator R ${pct}%` : `Fator R ${pct}%`;

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${
                fatorR.dentro
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                    : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
            }`}
        >
            {fatorR.dentro ? (
                <TrendingUp className="h-3 w-3" />
            ) : (
                <TrendingDown className="h-3 w-3" />
            )}
            {label}
            {fatorR.estimado && <span className="opacity-70"> (est.)</span>}
        </span>
    );
}

// ─── Controle inline por sócio ────────────────────────────────────────────────

interface SocioInlineFormProps {
    socio: SocioProlaboreDashboard;
    companyId: number;
    competencia: string; // 'YYYY-MM'
}

function SocioInlineForm({
    socio,
    companyId,
    competencia,
}: SocioInlineFormProps) {
    const isCreating = socio.record_id === null;

    const createForm = useForm<CreateProlaboreRecordPayload>({
        partner_id: socio.partner_id,
        competencia,
        valor: socio.valor ?? 0,
        observacao: null,
    });

    const updateForm = useForm<UpdateProlaboreRecordPayload>({
        competencia,
        valor: socio.valor ?? 0,
        observacao: null,
    });

    const processing = isCreating
        ? createForm.processing
        : updateForm.processing;
    const valorError = isCreating
        ? createForm.errors.valor
        : updateForm.errors.valor;
    const observacaoError = isCreating
        ? createForm.errors.observacao
        : updateForm.errors.observacao;
    const competenciaError = isCreating
        ? createForm.errors.competencia
        : updateForm.errors.competencia;
    const valor = isCreating ? createForm.data.valor : updateForm.data.valor;
    const observacao = isCreating
        ? createForm.data.observacao
        : updateForm.data.observacao;

    const setValor = (val: number) => {
        if (isCreating) createForm.setData('valor', val);
        else updateForm.setData('valor', val);
    };

    const setObservacao = (obs: string | null) => {
        if (isCreating) createForm.setData('observacao', obs);
        else updateForm.setData('observacao', obs);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (isCreating) {
            createForm.post(
                companies.prolaboreRecords.store({ company: companyId }).url,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        // O reload parcial é tratado pelo redirect()->back() do backend;
                        // o Inertia recarrega automaticamente as props da página corrente.
                    },
                },
            );
        } else {
            updateForm.put(
                companies.prolaboreRecords.update({
                    company: companyId,
                    record: socio.record_id as number,
                }).url,
                {
                    preserveScroll: true,
                },
            );
        }
    };

    return (
        <form onSubmit={handleSubmit} className="mt-2 space-y-2">
            {/* Input de valor com prefix R$ */}
            <div className="flex items-center gap-1.5">
                <span className="text-sm text-muted-foreground">R$</span>
                <div className="flex-1">
                    <Input
                        type="number"
                        min="0.01"
                        step="0.01"
                        value={valor}
                        onChange={(e) => setValor(Number(e.target.value))}
                        disabled={processing}
                        placeholder="0,00"
                        className="h-8 text-sm"
                    />
                    <InputError message={valorError} />
                </div>
            </div>

            {/* Textarea de observação */}
            <div>
                <textarea
                    value={observacao ?? ''}
                    onChange={(e) =>
                        setObservacao(
                            e.target.value.length > 0 ? e.target.value : null,
                        )
                    }
                    disabled={processing}
                    maxLength={500}
                    placeholder="Observacao (opcional)"
                    rows={2}
                    className="flex min-h-0 w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                />
                <InputError message={observacaoError} />
            </div>

            {/* Erros gerais (duplicata, competência inválida, partner fora da empresa) */}
            {isCreating && createForm.errors.partner_id && (
                <InputError message={createForm.errors.partner_id} />
            )}
            {competenciaError && <InputError message={competenciaError} />}

            <Button
                type="submit"
                size="sm"
                disabled={processing}
                className="w-full"
            >
                {isCreating ? 'Criar recibo' : 'Atualizar'}
            </Button>
        </form>
    );
}

// ─── Card principal ───────────────────────────────────────────────────────────

export function ProlaboreCard({ prolabore, companyId, isCurrentMonth }: Props) {
    const [open, setOpen] = useState(false);

    const hasDetail = prolabore.socios.length > 0 || prolabore.fator_r !== null;

    const isSemDados =
        prolabore.tipo === 'sem_faturamento' || prolabore.tipo === 'sem_config';

    // Mês corrente e tipo com socios → exibir controle inline
    const showInlineControl = isCurrentMonth && !isSemDados;

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <Card>
                <CardHeader className="pb-0">
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-base">Pró-labore</CardTitle>
                        <TipoBadge tipo={prolabore.tipo} />
                    </div>
                </CardHeader>
                <CardContent className="space-y-3">
                    <div className="flex items-end justify-between">
                        <div>
                            <p className="text-2xl font-bold">
                                {isSemDados ? '—' : brl(prolabore.total)}
                            </p>
                            {!isSemDados && (
                                <p className="text-xs text-muted-foreground">
                                    {prolabore.socios.length} sócio(s)
                                </p>
                            )}
                            {prolabore.fator_r && (
                                <div className="mt-1">
                                    <FatorRBadge fatorR={prolabore.fator_r} />
                                </div>
                            )}
                        </div>
                        {hasDetail && (
                            <CollapsibleTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-xs text-muted-foreground"
                                >
                                    {open ? (
                                        <>
                                            Ocultar{' '}
                                            <ChevronUp className="ml-1 h-3 w-3" />
                                        </>
                                    ) : (
                                        <>
                                            Detalhes{' '}
                                            <ChevronDown className="ml-1 h-3 w-3" />
                                        </>
                                    )}
                                </Button>
                            </CollapsibleTrigger>
                        )}
                    </div>

                    <CollapsibleContent>
                        <Separator className="mb-3" />

                        {/* Breakdown por sócio */}
                        {prolabore.socios.length > 0 && (
                            <div className="mb-3 space-y-3">
                                {prolabore.socios.map((s, i) => (
                                    <div key={i}>
                                        <div className="flex items-center justify-between py-1.5 text-sm">
                                            <span className="text-muted-foreground">
                                                {s.nome}
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <TipoBadge tipo={s.tipo} />
                                                <span className="font-medium">
                                                    {brl(s.valor)}
                                                </span>
                                            </div>
                                        </div>

                                        {showInlineControl && (
                                            <SocioInlineForm
                                                socio={s}
                                                companyId={companyId}
                                                competencia={
                                                    /* selectedMonthKey é passado como isCurrentMonth=true,
                                                       portanto usamos o mês atual da data de hoje */
                                                    new Date()
                                                        .toISOString()
                                                        .slice(0, 7)
                                                }
                                            />
                                        )}

                                        {i < prolabore.socios.length - 1 && (
                                            <Separator className="mt-2" />
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Detalhe do Fator R */}
                        {prolabore.fator_r && (
                            <div className="rounded-md bg-muted/50 p-3 text-xs text-muted-foreground">
                                <p className="mb-1 font-medium text-foreground">
                                    Fator R — últimos 12 meses
                                </p>
                                <div className="flex justify-between">
                                    <span>Pró-labore (folha12)</span>
                                    <span className="font-medium text-foreground">
                                        {brl(prolabore.fator_r.folha12)}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Receita bruta (RBT12)</span>
                                    <span className="font-medium text-foreground">
                                        {brl(prolabore.fator_r.rbt12)}
                                    </span>
                                </div>
                                <div className="mt-1 flex justify-between border-t pt-1">
                                    <span>Resultado</span>
                                    <span
                                        className={`font-semibold ${
                                            prolabore.fator_r.dentro
                                                ? 'text-green-700 dark:text-green-400'
                                                : 'text-red-700 dark:text-red-400'
                                        }`}
                                    >
                                        {(
                                            prolabore.fator_r.percentual * 100
                                        ).toFixed(1)}
                                        % —{' '}
                                        {prolabore.fator_r.dentro
                                            ? 'Anexo III ✓'
                                            : 'Anexo V ✗'}
                                    </span>
                                </div>
                                {prolabore.fator_r.estimado && (
                                    <p className="mt-1 italic opacity-70">
                                        * valores estimados com base em
                                        simulações
                                    </p>
                                )}
                            </div>
                        )}
                    </CollapsibleContent>
                </CardContent>
            </Card>
        </Collapsible>
    );
}
