import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Building2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { MOCK_DASHBOARD } from '@/fixtures/dashboard';
import companies from '@/routes/companies';
import { dashboard } from '@/routes';
import type { Company } from '@/types/companies';
import type { FaturamentoData } from '@/types/dashboard';
import { DasCard } from './dashboard/components/das-card';
import { FaturamentoCard } from './dashboard/components/faturamento-card';
import { GastosCard } from './dashboard/components/gastos-card';
import { ProlaboreCard } from './dashboard/components/prolabore-card';
import { CompetenciaNav } from './dashboard/components/competencia-nav';
import { generateMonths, todayMonthKey } from './dashboard/lib/months';

const EMPTY_FATURAMENTO: FaturamentoData = {
    total: 0,
    notas_emitidas: 0,
    itens: [],
};

interface PageProps {
    companies: Company[];
    faturamentoPorEmpresa: Record<string, Record<string, FaturamentoData>>;
    [key: string]: unknown;
}

const TODAY = new Date();
const MONTHS = generateMonths(TODAY);
const CURRENT_MONTH_KEY = todayMonthKey(TODAY);

export default function Dashboard({
    companies: companyList,
    faturamentoPorEmpresa,
}: PageProps) {
    const [selectedCompanyId, setSelectedCompanyId] = useState<number | null>(
        companyList[0]?.id ?? null,
    );
    const [selectedMonthKey, setSelectedMonthKey] = useState(CURRENT_MONTH_KEY);

    const selectedMonth = useMemo(
        () => MONTHS.find((m) => m.key === selectedMonthKey) ?? MONTHS[6],
        [selectedMonthKey],
    );

    const currentFaturamento = useMemo(
        () =>
            (selectedCompanyId !== null
                ? faturamentoPorEmpresa[String(selectedCompanyId)]?.[
                      selectedMonthKey
                  ]
                : undefined) ?? EMPTY_FATURAMENTO,
        [faturamentoPorEmpresa, selectedCompanyId, selectedMonthKey],
    );

    if (companyList.length === 0) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-12 text-center">
                    <Building2 className="h-10 w-10 text-muted-foreground" />
                    <div>
                        <p className="font-medium">
                            Nenhuma empresa cadastrada
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Cadastre uma empresa para ver o dashboard
                            financeiro.
                        </p>
                    </div>
                    <Link
                        href={companies.index().url}
                        className="text-sm text-primary underline underline-offset-4"
                    >
                        Ir para Empresas
                    </Link>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-6">
                {/* Cabeçalho */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Dashboard</h1>
                        <p className="text-sm text-muted-foreground">
                            Panorama financeiro — {selectedMonth.label}
                        </p>
                    </div>
                    <Select
                        value={String(selectedCompanyId)}
                        onValueChange={(v) => setSelectedCompanyId(Number(v))}
                    >
                        <SelectTrigger className="w-64">
                            <SelectValue placeholder="Selecione uma empresa" />
                        </SelectTrigger>
                        <SelectContent>
                            {companyList.map((c) => (
                                <SelectItem key={c.id} value={String(c.id)}>
                                    {c.razao_social}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Aviso dados simulados */}
                <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                    <span>
                        DAS, Pró-labore e Gastos ainda usam valores fictícios.
                        Faturamento já exibe dados reais.
                    </span>
                </div>

                {/* Navegação por competência */}
                <CompetenciaNav
                    months={MONTHS}
                    selectedKey={selectedMonthKey}
                    onSelect={setSelectedMonthKey}
                />

                {/* Cards de resumo financeiro */}
                <div className="grid gap-4 md:grid-cols-2">
                    <DasCard das={MOCK_DASHBOARD.das} />
                    <ProlaboreCard prolabore={MOCK_DASHBOARD.prolabore} />
                    <FaturamentoCard faturamento={currentFaturamento} />
                    <GastosCard gastos={MOCK_DASHBOARD.gastos} />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard().url }],
};
