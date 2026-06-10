<?php

declare(strict_types=1);

namespace Src\Companies\Application\DTOs;

final readonly class PartnerData
{
    public function __construct(
        public string $nome,
        public string $cpf,
        public float $participacao,
    ) {}
}
