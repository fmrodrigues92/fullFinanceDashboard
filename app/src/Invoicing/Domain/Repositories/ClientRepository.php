<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain\Repositories;

use Src\Invoicing\Application\DTOs\PaginatedResult;
use Src\Invoicing\Domain\Client;

interface ClientRepository
{
    public function save(Client $client): Client;

    public function findForCompany(int $id, int $companyId): ?Client;

    /** @return Client[] */
    public function allForCompany(int $companyId): array;

    public function paginatedForCompany(int $companyId, int $page, int $perPage): PaginatedResult;

    public function softDelete(Client $client): void;

    public function nullifyClientOnInvoices(int $clientId): void;
}
