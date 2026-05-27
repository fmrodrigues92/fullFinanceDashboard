<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Application\DTOs\CreateProlaboreConfigInput;
use Src\Companies\Domain\Exceptions\PartnerNotBelongsToCompany;
use Src\Companies\Domain\ProlaboreConfig;
use Src\Companies\Domain\Repositories\CompanyRepository;
use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;

final readonly class CreateProlaboreConfigUseCase
{
    public function __construct(
        private ProlaboreConfigRepository $configRepository,
        private CompanyRepository $companyRepository,
    ) {}

    public function __invoke(CreateProlaboreConfigInput $input): ProlaboreConfig
    {
        $partner = $this->companyRepository->findPartner($input->partnerId, $input->companyId);

        if ($partner === null) {
            throw new PartnerNotBelongsToCompany(
                "Sócio {$input->partnerId} não pertence à empresa {$input->companyId}.",
            );
        }

        $config = ProlaboreConfig::create(
            companyId: $input->companyId,
            partnerId: $input->partnerId,
            userId: $input->userId,
            valor: $input->valor,
        );

        return $this->configRepository->save($config);
    }
}
