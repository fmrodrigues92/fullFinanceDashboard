export type RegimeTributario =
    | 'mei'
    | 'simples_nacional'
    | 'lucro_presumido'
    | 'lucro_real';

export interface Company {
    id: number;
    razao_social: string;
    nome_fantasia: string;
    cnpj: string;
    cnpj_formatted: string;
    regime_tributario: RegimeTributario;
    regime_tributario_label: string;
}

export interface CompanyPartner {
    id: number;
    nome: string;
    cpf: string;
    participacao: number;
}

export interface PartnerData {
    nome: string;
    cpf: string;
    participacao: number;
}

export interface ProlaboreConfig {
    id: number;
    company_id: number;
    partner_id: number;
    user_id: number;
    valor: number;
}

export interface ProlaboreRecord {
    id: number;
    company_id: number;
    partner_id: number;
    user_id: number;
    competencia: string;
    valor: number;
    observacao: string | null;
}

export const REGIME_OPTIONS: { value: RegimeTributario; label: string }[] = [
    { value: 'mei', label: 'MEI' },
    { value: 'simples_nacional', label: 'Simples Nacional' },
    { value: 'lucro_presumido', label: 'Lucro Presumido' },
    { value: 'lucro_real', label: 'Lucro Real' },
];
