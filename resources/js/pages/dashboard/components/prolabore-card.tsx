import { useState } from 'react';
import { ChevronDown, ChevronUp, TrendingDown, TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Separator } from '@/components/ui/separator';
import type {
    FatorR,
    ProlaboreDashboardData,
    ProlaboreTipo,
} from '@/types/dashboard';
import { brl } from '../lib/format';

interface Props {
    prolabore: ProlaboreDashboardData;
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

// ─── Card principal ───────────────────────────────────────────────────────────

export function ProlaboreCard({ prolabore }: Props) {
    const [open, setOpen] = useState(false);

    const hasDetail = prolabore.socios.length > 0 || prolabore.fator_r !== null;

    const isSemDados =
        prolabore.tipo === 'sem_faturamento' || prolabore.tipo === 'sem_config';

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
                            <table className="mb-3 w-full text-sm">
                                <tbody>
                                    {prolabore.socios.map((s, i) => (
                                        <tr
                                            key={i}
                                            className="border-b last:border-0"
                                        >
                                            <td className="py-1.5 text-muted-foreground">
                                                {s.nome}
                                            </td>
                                            <td className="py-1.5 text-center">
                                                <TipoBadge tipo={s.tipo} />
                                            </td>
                                            <td className="py-1.5 text-right font-medium">
                                                {brl(s.valor)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
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
