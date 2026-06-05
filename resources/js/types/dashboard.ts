import type { Company } from './companies';

export interface DasDetalhe {
    descricao: string;
    valor: number;
}

export interface DasData {
    valor: number;
    vencimento: string; // 'DD/MM/YYYY'
    status: 'pendente' | 'pago';
    detalhes: DasDetalhe[];
}

export interface FaturamentoItem {
    tipo: 'nacional' | 'internacional';
    valor: number;
    quantidade: number;
}

export interface FaturamentoData {
    total: number;
    notas_emitidas: number;
    itens: FaturamentoItem[];
    is_simulado: boolean;
}

export interface GastoItem {
    categoria: string;
    descricao: string;
    valor: number;
}

export interface GastosData {
    total: number;
    itens: GastoItem[];
}

// ─── Pró-labore (dados reais — feature 005) ───────────────────────────────────

export type ProlaboreTipo =
    | 'recibo_manual'
    | 'recibo_automatico'
    | 'sem_recibo'
    | 'previsao'
    | 'sem_faturamento'
    | 'sem_config';

export interface FatorR {
    percentual: number; // ex: 0.312 = 31.2%
    dentro: boolean; // >= 0.28 → Anexo III
    estimado: boolean; // calculado com dados projetados
    rbt12: number;
    folha12: number;
}

export interface SocioProlaboreDashboard {
    nome: string;
    valor: number;
    tipo: ProlaboreTipo;
    partner_id: number; // id do sócio
    record_id: number | null; // id do recibo se existir; null = criar
}

// ─── Payloads de escrita (feature 006) ───────────────────────────────────────

export interface CreateProlaboreRecordPayload {
    partner_id: number;
    competencia: string; // 'YYYY-MM' (mês corrente)
    valor: number; // > 0
    observacao: string | null; // max 500
}

export interface UpdateProlaboreRecordPayload {
    competencia: string; // 'YYYY-MM' (mês corrente)
    valor: number; // > 0
    observacao: string | null;
}

export interface ProlaboreRecord {
    id: number;
    company_id: number;
    partner_id: number;
    user_id: number;
    competencia: string; // 'YYYY-MM-01'
    valor: number;
    observacao: string | null;
    origem: 'manual' | 'automatico';
}

export interface ProlaboreDashboardData {
    total: number;
    tipo: ProlaboreTipo;
    fator_r: FatorR | null;
    socios: SocioProlaboreDashboard[];
}

// ─── Mock (legado — DAS e Gastos ainda usam) ──────────────────────────────────

/** @deprecated usar ProlaboreDashboardData para pró-labore */
export interface SocioProLabore {
    nome: string;
    valor: number;
    status: 'pago' | 'pendente';
}

/** @deprecated usar ProlaboreDashboardData para pró-labore */
export interface ProlaboreData {
    total: number;
    socios: SocioProLabore[];
}

export interface DashboardMockData {
    das: DasData;
    prolabore: ProlaboreData;
    faturamento: FaturamentoData;
    gastos: GastosData;
}

// ─── Props da página ─────────────────────────────────────────────────────────

export interface DashboardProps {
    companies: Company[];
    /** companyId (string) → competencia 'YYYY-MM' → dados reais */
    faturamentoPorEmpresa: Record<string, Record<string, FaturamentoData>>;
    /** companyId (string) → competencia 'YYYY-MM' → dados reais + Fator R */
    prolaborePorEmpresa: Record<string, Record<string, ProlaboreDashboardData>>;
}
