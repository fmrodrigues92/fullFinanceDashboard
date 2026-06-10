<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\SimulationBatch;

use Src\Invoicing\Domain\Repositories\SimulationBatchRepository;
use Src\Invoicing\Domain\SimulationBatch;

final readonly class ListSimulationBatchesUseCase
{
    public function __construct(private SimulationBatchRepository $repository) {}

    /** @return SimulationBatch[] */
    public function __invoke(int $companyId): array
    {
        return $this->repository->allForCompany($companyId);
    }
}
