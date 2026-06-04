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
}
