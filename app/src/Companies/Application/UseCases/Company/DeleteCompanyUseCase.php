<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases\Company;

use Src\Companies\Domain\Repositories\CompanyRepository;

final readonly class DeleteCompanyUseCase
{
    public function __construct(private CompanyRepository $repository) {}

    public function __invoke(int $companyId, int $userId): void
    {
        $company = $this->repository->findForUser($companyId, $userId);

        if ($company === null) {
            return;
        }

        $this->repository->delete($company);
    }
}
