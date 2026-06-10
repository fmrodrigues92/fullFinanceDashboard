<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Invoice;

use Src\Invoicing\Domain\Repositories\InvoiceRepository;

final readonly class DeleteInvoiceUseCase
{
    public function __construct(private InvoiceRepository $repository) {}

    public function __invoke(int $invoiceId, int $companyId): void
    {
        $invoice = $this->repository->findForCompany($invoiceId, $companyId);

        if ($invoice === null) {
            return;
        }

        $this->repository->softDelete($invoice);
    }
}
