<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Invoice;

use Src\Invoicing\Application\DTOs\ListInvoicesFilter;
use Src\Invoicing\Application\DTOs\PaginatedResult;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;

final readonly class ListInvoicesUseCase
{
    public function __construct(private InvoiceRepository $repository) {}

    public function __invoke(ListInvoicesFilter $filter): PaginatedResult
    {
        return $this->repository->listForCompany($filter);
    }
}
