<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Invoice;

use DateTimeImmutable;
use Src\Invoicing\Application\DTOs\CreateInvoiceInput;
use Src\Invoicing\Domain\Enums\InvoiceTipo;
use Src\Invoicing\Domain\Invoice;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;
use Src\Invoicing\Domain\ValueObjects\AnexoCnae;

final readonly class CreateInvoiceUseCase
{
    public function __construct(private InvoiceRepository $repository) {}

    public function __invoke(CreateInvoiceInput $input): Invoice
    {
        $invoice = Invoice::createReal(
            companyId: $input->companyId,
            clientId: $input->clientId,
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
