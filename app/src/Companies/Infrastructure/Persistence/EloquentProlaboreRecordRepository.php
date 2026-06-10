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
            'origem' => $record->origem,
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

    public function existsForPartnerAndCompetencia(int $companyId, int $partnerId, DateTimeImmutable $competencia): bool
    {
        return ProlaboreRecordModel::query()
            ->where('company_id', $companyId)
            ->where('partner_id', $partnerId)
            ->where('competencia', $competencia->format('Y-m-d'))
            ->exists();
    }

    /**
     * @param  int[]  $companyIds
     * @return array<int, array<string, array<int, array{id: int, valor: float, origem: string}>>>
     */
    public function dashboardRecordsForCompanies(array $companyIds, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if (empty($companyIds)) {
            return [];
        }

        $rows = ProlaboreRecordModel::query()
            ->whereIn('company_id', $companyIds)
            ->where('competencia', '>=', $from->format('Y-m-d'))
            ->where('competencia', '<', $to->format('Y-m-d'))
            ->get(['id', 'company_id', 'partner_id', 'competencia', 'valor', 'origem']);

        $result = [];
        foreach ($rows as $row) {
            $cid = (int) $row->company_id;
            $pid = (int) $row->partner_id;
            $comp = substr((string) $row->competencia, 0, 7); // YYYY-MM
            $result[$cid][$comp][$pid] = [
                'id' => (int) $row->id,
                'valor' => (float) $row->valor,
                'origem' => (string) $row->origem,
            ];
        }

        return $result;
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
            origem: (string) ($m->origem ?? 'manual'),
        );
    }
}
