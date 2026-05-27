<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\DTOs;

final readonly class UpdateClientInput
{
    public function __construct(
        public int $clientId,
        public int $companyId,
        public string $nome,
        public ?string $extId,
    ) {}
}
