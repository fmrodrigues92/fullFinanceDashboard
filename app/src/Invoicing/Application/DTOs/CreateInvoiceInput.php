<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\DTOs;

final readonly class CreateInvoiceInput
{
    public function __construct(
        public int $companyId,
        public int $clientId,
        public string $tipo,
        public int $anexoCnae,
        public string $dataEmissao,
        public string $valorBrl,
        public ?string $valorUsd,
        public ?string $cotacao,
        public ?string $observacao,
    ) {}
}
