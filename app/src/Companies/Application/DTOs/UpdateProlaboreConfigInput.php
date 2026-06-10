<?php

declare(strict_types=1);

namespace Src\Companies\Application\DTOs;

final readonly class UpdateProlaboreConfigInput
{
    public function __construct(
        public int $configId,
        public int $companyId,
        public float $valor,
        public string $tipo = 'fixo',
    ) {}
}
