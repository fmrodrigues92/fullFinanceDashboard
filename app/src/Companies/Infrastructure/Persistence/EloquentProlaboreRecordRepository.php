<?php

declare(strict_types=1);

namespace Src\Companies\Infrastructure\Persistence;

use DateTimeImmutable;
use Src\Companies\Domain\ProlaboreRecord;
use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;

final class EloquentProlaboreRecordRepository implements ProlaboreRecordRepository
{
    public function save(ProlaboreRecord $record): ProlaboreRecord
    {
        $data = [
            'company_id' => $record->companyId,
            'partner_id' => $record->partnerId,
            'user_id' => $record->userId,
            'competencia' => $record->competencia->format('Y-m-d'),
            'valor' => $record->valor,
            'observacao' => $record->observacao,
        ];

        if ($record->id !== null) {
            $model = ProlaboreRecordModel::query()
                ->where('id', $record->id)
                ->where('company_id', $record->companyId)
                ->firstOrFail();
            $model->update($data);
        } else {
            $model = ProlaboreRecordModel::query()->create($data);
        }

        return $this->toDomain($model);
    }

    public function findForCompany(int $id, int $companyId): ?ProlaboreRecord
    {
        $model = ProlaboreRecordModel::query()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /** @return ProlaboreRecord[] */
    public function allForCompany(int $companyId, ?DateTimeImmutable $competencia = null): array
    {
        $query = ProlaboreRecordModel::query()
            ->where('company_id', $companyId)
            ->orderBy('competencia', 'desc');

        if ($competencia !== null) {
            $query->where('competencia', $competencia->format('Y-m-d'));
        }

        return $query
            ->get()
            ->map(fn (ProlaboreRecordModel $m) => $this->toDomain($m))
            ->all();
    }

    public function delete(ProlaboreRecord $record): void
    {
        ProlaboreRecordModel::query()
            ->where('id', $record->id)
            ->where('company_id', $record->companyId)
            ->delete();
    }

    private function toDomain(ProlaboreRecordModel $m): ProlaboreRecord
    {
        return ProlaboreRecord::fromPersistence(
            id: (int) $m->id,
            companyId: (int) $m->company_id,
            partnerId: (int) $m->partner_id,
            userId: (int) $m->user_id,
            competencia: new DateTimeImmutable((string) $m->competencia),
            valor: (float) $m->valor,
            observacao: $m->observacao !== null ? (string) $m->observacao : null,
        );
    }
}
