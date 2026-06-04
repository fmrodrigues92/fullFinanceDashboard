import type { DashboardMockData } from '@/types/dashboard';

export const MOCK_DASHBOARD: DashboardMockData = {
    das: {
        valor: 1234.56,
        vencimento: '20/05/2026',
        status: 'pendente',
        detalhes: [
            { descricao: 'IRPJ', valor: 312.5 },
            { descricao: 'CSLL', valor: 280.0 },
            { descricao: 'PIS/COFINS', valor: 420.06 },
            { descricao: 'CPP', valor: 222.0 },
        ],
    },
    prolabore: {
        total: 10000.0,
        socios: [
            { nome: 'João Silva', valor: 5000.0, status: 'pago' },
            { nome: 'Maria Souza', valor: 5000.0, status: 'pendente' },
        ],
    },
    faturamento: {
        total: 35000.0,
        notas_emitidas: 4,
        itens: [
            { tipo: 'nacional', valor: 25000.0, quantidade: 3 },
            { tipo: 'internacional', valor: 10000.0, quantidade: 1 },
        ],
    },
    gastos: {
        total: 2000.0,
        itens: [
            { categoria: 'INSS', descricao: 'INSS empregador', valor: 500.0 },
            {
                categoria: 'Cartão',
                descricao: 'Cartão corporativo XP',
                valor: 1200.0,
            },
            { categoria: 'Taxas', descricao: 'Outras taxas', valor: 300.0 },
        ],
    },
};
