<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain\Repositories;

use Src\Invoicing\Domain\SimulationBatch;

interface SimulationBatchRepository
{
    public function save(SimulationBatch $batch): SimulationBatch;

    public function findForCompany(int $id, int $companyId): ?SimulationBatch;

    /** @return SimulationBatch[] */
    public function allForCompany(int $companyId): array;

    public function delete(SimulationBatch $batch): void;
}
