<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Application\DTOs\UpdateProlaboreRecordInput;
use Src\Companies\Domain\ProlaboreRecord;
use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;

final readonly class UpdateProlaboreRecordUseCase
{
    public function __construct(private ProlaboreRecordRepository $repository) {}

    public function __invoke(UpdateProlaboreRecordInput $input): ProlaboreRecord
    {
        $record = $this->repository->findForCompany($input->recordId, $input->companyId);

        if ($record === null) {
            throw new \RuntimeException('Recibo de pró-labore não encontrado.');
        }

        return $this->repository->save($record->update(
            competencia: $input->competencia,
            valor: $input->valor,
            observacao: $input->observacao,
        ));
    }
}
