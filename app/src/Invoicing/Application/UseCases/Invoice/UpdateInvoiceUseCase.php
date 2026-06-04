<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Invoice;

use DateTimeImmutable;
use Src\Invoicing\Application\DTOs\UpdateInvoiceInput;
use Src\Invoicing\Domain\Enums\InvoiceTipo;
use Src\Invoicing\Domain\Invoice;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;
use Src\Invoicing\Domain\ValueObjects\AnexoCnae;

final readonly class UpdateInvoiceUseCase
{
    public function __construct(private InvoiceRepository $repository) {}

    public function __invoke(UpdateInvoiceInput $input): Invoice
    {
        $invoice = Invoice::fromPersistence(
            id: $input->invoiceId,
            companyId: $input->companyId,
            clientId: $input->clientId,
            simulationBatchId: null,
            isSimulation: false,
            tipo: InvoiceTipo::from($input->tipo),
            anexoCnae: new AnexoCnae($input->anexoCnae),
            dataEmissao: new DateTimeImmutable($input->dataEmissao),
            valorBrl: $input->valorBrl,
            valorUsd: $input->valorUsd,
            cotacao: $input->cotacao,
            observacao: $input->observacao,
        );

        return $this->repository->save($invoice);
    }
}
