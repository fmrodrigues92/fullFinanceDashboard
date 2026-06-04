<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Invoice;

use Src\Invoicing\Domain\Repositories\InvoiceRepository;

final readonly class GetFaturamentoDashboardUseCase
{
    public function __construct(private InvoiceRepository $repository) {}

    /**
     * @param  string[]  $competencias  'YYYY-MM'
     * @return array<string, array{total: float, notas_emitidas: int, itens: list<array{tipo: string, valor: float, quantidade: int}>}>
     */
    public function __invoke(int $companyId, array $competencias): array
    {
        return $this->repository->faturamentoPorCompetencias($companyId, $competencias);
    }
}
