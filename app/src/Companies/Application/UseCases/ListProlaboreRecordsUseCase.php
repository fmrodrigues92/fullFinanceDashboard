<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use DateTimeImmutable;
use Src\Companies\Domain\ProlaboreRecord;
use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;

final readonly class ListProlaboreRecordsUseCase
{
    public function __construct(private ProlaboreRecordRepository $repository) {}

    /** @return ProlaboreRecord[] */
    public function __invoke(int $companyId, ?DateTimeImmutable $competencia = null): array
    {
        return $this->repository->allForCompany($companyId, $competencia);
    }
}
