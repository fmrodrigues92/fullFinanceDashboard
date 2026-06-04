<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases\ProlaboreRecord;

use Src\Companies\Application\DTOs\CreateProlaboreRecordInput;
use Src\Companies\Domain\Exceptions\PartnerNotBelongsToCompany;
use Src\Companies\Domain\ProlaboreRecord;
use Src\Companies\Domain\Repositories\CompanyRepository;
use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;

final readonly class CreateProlaboreRecordUseCase
{
    public function __construct(
        private ProlaboreRecordRepository $recordRepository,
        private CompanyRepository $companyRepository,
    ) {}

    public function __invoke(CreateProlaboreRecordInput $input): ProlaboreRecord
    {
        $partner = $this->companyRepository->findPartner($input->partnerId, $input->companyId);

        if ($partner === null) {
            throw new PartnerNotBelongsToCompany(
                "Sócio {$input->partnerId} não pertence à empresa {$input->companyId}.",
            );
        }

        $record = ProlaboreRecord::create(
            companyId: $input->companyId,
            partnerId: $input->partnerId,
            userId: $input->userId,
            competencia: $input->competencia,
            valor: $input->valor,
            observacao: $input->observacao,
        );

        return $this->recordRepository->save($record);
    }
}
