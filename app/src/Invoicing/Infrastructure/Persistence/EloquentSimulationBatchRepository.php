<?php

declare(strict_types=1);

namespace Src\Invoicing\Infrastructure\Persistence;

use Illuminate\Support\Facades\Cache;
use Src\Invoicing\Domain\Repositories\SimulationBatchRepository;
use Src\Invoicing\Domain\SimulationBatch;

final class EloquentSimulationBatchRepository implements SimulationBatchRepository
{
    public function save(SimulationBatch $batch): SimulationBatch
    {
        $model = SimulationBatchModel::query()->create([
            'company_id' => $batch->companyId,
        ]);

        Cache::forget("invoicing:simulations:{$batch->companyId}");

        return $this->toDomain($model);
    }

    public function findForCompany(int $id, int $companyId): ?SimulationBatch
    {
        $model = SimulationBatchModel::query()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /** @return SimulationBatch[] */
    public function allForCompany(int $companyId): array
    {
        return SimulationBatchModel::query()
            ->where('company_id', $companyId)
            ->latest()
            ->get()
            ->map(fn (SimulationBatchModel $m) => $this->toDomain($m))
            ->all();
    }

    public function delete(SimulationBatch $batch): void
    {
        SimulationBatchModel::query()
            ->where('id', $batch->id)
            ->where('company_id', $batch->companyId)
            ->delete();

        Cache::forget("invoicing:simulations:{$batch->companyId}");
    }

    private function toDomain(SimulationBatchModel $m): SimulationBatch
    {
        return SimulationBatch::fromPersistence(
            id: (int) $m->id,
            companyId: (int) $m->company_id,
        );
    }
}
