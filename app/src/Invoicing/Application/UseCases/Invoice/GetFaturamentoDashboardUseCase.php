<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Invoice;

use Src\Invoicing\Domain\Repositories\InvoiceRepository;

final readonly class GetFaturamentoDashboardUseCase
{
    public function __construct(private InvoiceRepository $repository) {}

    /**
     * @param  int[]  $companyIds
     * @param  string[]  $competencias  'YYYY-MM'
     * @return array<string, array<string, array{total: float, notas_emitidas: int, itens: list<array{tipo: string, valor: float, quantidade: int}>}>>
     *                                                                                                                                                 [companyId][YYYY-MM] => data
     */
    public function __invoke(array $companyIds, array $competencias): array
    {
        if (empty($companyIds) || empty($competencias)) {
            return [];
        }

        return $this->repository->faturamentoPorCompetenciasMultiEmpresa($companyIds, $competencias);
    }
}
