<?php

declare(strict_types=1);

namespace Src\Companies\Infrastructure\Persistence;

use Src\Companies\Domain\ProlaboreConfig;
use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;

final class EloquentProlaboreConfigRepository implements ProlaboreConfigRepository
{
    public function save(ProlaboreConfig $config): ProlaboreConfig
    {
        $data = [
            'company_id' => $config->companyId,
            'partner_id' => $config->partnerId,
            'user_id' => $config->userId,
            'valor' => $config->valor,
        ];

        if ($config->id !== null) {
            $model = ProlaboreConfigModel::query()
                ->where('id', $config->id)
                ->where('company_id', $config->companyId)
                ->firstOrFail();
            $model->update($data);
        } else {
            $model = ProlaboreConfigModel::query()->create($data);
        }

        return $this->toDomain($model);
    }

    public function findForCompany(int $id, int $companyId): ?ProlaboreConfig
    {
        $model = ProlaboreConfigModel::query()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /** @return ProlaboreConfig[] */
    public function allForCompany(int $companyId): array
    {
        return ProlaboreConfigModel::query()
            ->where('company_id', $companyId)
            ->get()
            ->map(fn (ProlaboreConfigModel $m) => $this->toDomain($m))
            ->all();
    }

    public function delete(ProlaboreConfig $config): void
    {
        ProlaboreConfigModel::query()
            ->where('id', $config->id)
            ->where('company_id', $config->companyId)
            ->delete();
    }

    private function toDomain(ProlaboreConfigModel $m): ProlaboreConfig
    {
        return ProlaboreConfig::fromPersistence(
            id: (int) $m->id,
            companyId: (int) $m->company_id,
            partnerId: (int) $m->partner_id,
            userId: (int) $m->user_id,
            valor: (float) $m->valor,
        );
    }
}
