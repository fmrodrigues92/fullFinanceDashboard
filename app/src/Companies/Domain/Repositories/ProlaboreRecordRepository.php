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
}
