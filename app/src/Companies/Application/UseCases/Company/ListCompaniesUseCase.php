<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases\Company;

use Src\Companies\Domain\Company;
use Src\Companies\Domain\Repositories\CompanyRepository;

final readonly class ListCompaniesUseCase
{
    public function __construct(private CompanyRepository $repository) {}

    /** @return Company[] */
    public function __invoke(int $userId): array
    {
        return $this->repository->allForUser($userId);
    }
}
