<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain\Repositories;

use Src\Invoicing\Application\DTOs\ListInvoicesFilter;
use Src\Invoicing\Application\DTOs\PaginatedResult;
use Src\Invoicing\Domain\Invoice;

interface InvoiceRepository
{
    public function save(Invoice $invoice): Invoice;

    public function findForCompany(int $id, int $companyId): ?Invoice;

    public function listForCompany(ListInvoicesFilter $filter): PaginatedResult;

    public function softDelete(Invoice $invoice): void;

    /**
     * @param  string[]  $dates  YYYY-MM-DD (first day of each month)
     * @return string[] conflicting dates
     */
    public function conflictingSimulationDates(int $companyId, string $tipo, array $dates): array;

    /** @param Invoice[] $invoices */
    public function insertMany(array $invoices): void;

    /**
     * @param  string[]  $competencias  'YYYY-MM'
     * @return array<string, array{total: float, notas_emitidas: int, itens: list<array{tipo: string, valor: float, quantidade: int}>}>
     */
    public function faturamentoPorCompetencias(int $companyId, array $competencias): array;

    /**
     * Versão multi-empresa de faturamentoPorCompetencias.
     * Prioriza notas reais (is_simulation=false); usa simuladas quando não há notas reais no mês.
     * Inclui flag is_simulado=true quando o total exibido vem de simulação.
     *
     * @param  int[]  $companyIds
     * @param  string[]  $competencias  'YYYY-MM'
     * @return array<string, array<string, array{total: float, notas_emitidas: int, itens: list<array{tipo: string, valor: float, quantidade: int}>, is_simulado: bool}>>
     *                                                                                                                                                                    [companyId][YYYY-MM] => data
     */
    public function faturamentoPorCompetenciasMultiEmpresa(array $companyIds, array $competencias): array;

    /**
     * Soma de faturamento real e simulado por empresa e mês, para janela do dashboard + Fator R.
     * Retorna apenas meses com ao menos uma invoice.
     *
     * @param  int[]  $companyIds
     * @return array<int, array<string, array{real: float, simulado: float}>>
     *                                                                        [companyId][YYYY-MM] => ['real' => sum_brl, 'simulado' => sum_brl]
     */
    public function faturamentoSummaryForCompanies(
        array $companyIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;
}
