<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;

final readonly class DeleteProlaboreRecordUseCase
{
    public function __construct(private ProlaboreRecordRepository $repository) {}

    public function __invoke(int $recordId, int $companyId): void
    {
        $record = $this->repository->findForCompany($recordId, $companyId);

        if ($record === null) {
            return;
        }

        $this->repository->delete($record);
    }
}
