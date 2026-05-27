export type InvoiceTipo = 'nacional' | 'internacional';
export type AnexoCnae = 3 | 5;

export interface Client {
    id: number;
    company_id: number;
    nome: string;
    ext_id: string | null;
}

export interface Invoice {
    id: number;
    company_id: number;
    client_id: number | null;
    simulation_batch_id: number | null;
    is_simulation: boolean;
    tipo: InvoiceTipo;
    anexo_cnae: AnexoCnae;
    data_emissao: string;
    valor_brl: string;
    valor_usd: string | null;
    cotacao: string | null;
    observacao: string | null;
}

export interface SimulationBatch {
    id: number;
    company_id: number;
}

export interface ClientOption {
    id: number;
    nome: string;
}

export const ANEXO_OPTIONS: { value: string; label: string }[] = [
    { value: '3', label: 'Anexo III' },
    { value: '5', label: 'Anexo V' },
];

export const TIPO_OPTIONS: { value: InvoiceTipo; label: string }[] = [
    { value: 'nacional', label: 'Nacional' },
    { value: 'internacional', label: 'Internacional' },
];

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
