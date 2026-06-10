<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Client;

use Src\Invoicing\Domain\Client;
use Src\Invoicing\Domain\Repositories\ClientRepository;

final readonly class ListClientsUseCase
{
    public function __construct(private ClientRepository $repository) {}

    /** @return Client[] */
    public function __invoke(int $companyId): array
    {
        return $this->repository->allForCompany($companyId);
    }
}
