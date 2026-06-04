<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases\Company;

use Src\Companies\Domain\Company;
use Src\Companies\Domain\Repositories\CompanyRepository;

final readonly class GetCompanyUseCase
{
    public function __construct(private CompanyRepository $repository) {}

    public function __invoke(int $companyId, int $userId): ?Company
    {
        return $this->repository->findForUser($companyId, $userId);
    }
}
