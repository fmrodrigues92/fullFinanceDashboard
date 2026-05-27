<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\DTOs;

final readonly class ListInvoicesFilter
{
    public function __construct(
        public int $companyId,
        public ?bool $isSimulation = null,
        public ?string $competencia = null,
        public ?string $tipo = null,
        public int $page = 1,
        public int $perPage = 25,
    ) {}
}
