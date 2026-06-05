<?php

declare(strict_types=1);

namespace Src\Companies\Domain\Repositories;

use DateTimeImmutable;
use Src\Companies\Domain\ProlaboreRecord;

interface ProlaboreRecordRepository
{
    public function save(ProlaboreRecord $record): ProlaboreRecord;

    public function findForCompany(int $id, int $companyId): ?ProlaboreRecord;

    /** @return ProlaboreRecord[] */
    public function allForCompany(int $companyId, ?DateTimeImmutable $competencia = null): array;

    public function delete(ProlaboreRecord $record): void;

    public function existsForPartnerAndCompetencia(int $companyId, int $partnerId, DateTimeImmutable $competencia): bool;

    /**
     * Retorna registros agrupados por [company_id][YYYY-MM][partner_id] para o dashboard.
     *
     * @param  int[]  $companyIds
     * @return array<int, array<string, array<int, array{valor: float, origem: string}>>>
     */
    public function dashboardRecordsForCompanies(array $companyIds, DateTimeImmutable $from, DateTimeImmutable $to): array;
}
