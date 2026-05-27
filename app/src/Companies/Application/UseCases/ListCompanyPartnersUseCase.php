<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Domain\CompanyPartner;
use Src\Companies\Domain\Repositories\CompanyRepository;

final readonly class ListCompanyPartnersUseCase
{
    public function __construct(private CompanyRepository $repository) {}

    /** @return CompanyPartner[] */
    public function __invoke(int $companyId): array
    {
        return $this->repository->partnersForCompany($companyId);
    }
}
