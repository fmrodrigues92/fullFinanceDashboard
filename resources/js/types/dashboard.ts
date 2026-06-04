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

export interface SocioProLabore {
    nome: string;
    valor: number;
    status: 'pago' | 'pendente';
}

export interface ProlaboreData {
    total: number;
    socios: SocioProLabore[];
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

export interface DashboardMockData {
    das: DasData;
    prolabore: ProlaboreData;
    faturamento: FaturamentoData;
    gastos: GastosData;
}

export interface DashboardProps {
    companies: Company[];
}
