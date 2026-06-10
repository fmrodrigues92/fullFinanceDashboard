<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\SimulationBatch;

use Src\Invoicing\Domain\Repositories\SimulationBatchRepository;

final readonly class DeleteSimulationBatchUseCase
{
    public function __construct(private SimulationBatchRepository $repository) {}

    public function __invoke(int $batchId, int $companyId): void
    {
        $batch = $this->repository->findForCompany($batchId, $companyId);

        if ($batch === null) {
            return;
        }

        $this->repository->delete($batch);
    }
}
