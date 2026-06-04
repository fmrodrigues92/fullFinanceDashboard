<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Client;

use Src\Invoicing\Application\DTOs\PaginatedResult;
use Src\Invoicing\Domain\Repositories\ClientRepository;

final readonly class ListClientsPaginatedUseCase
{
    public function __construct(private ClientRepository $repository) {}

    public function __invoke(int $companyId, int $page, int $perPage): PaginatedResult
    {
        return $this->repository->paginatedForCompany($companyId, $page, $perPage);
    }
}
