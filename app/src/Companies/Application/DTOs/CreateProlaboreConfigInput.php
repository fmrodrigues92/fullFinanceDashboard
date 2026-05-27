<?php

declare(strict_types=1);

namespace Src\Companies\Application\DTOs;

final readonly class CreateProlaboreConfigInput
{
    public function __construct(
        public int $companyId,
        public int $partnerId,
        public int $userId,
        public float $valor,
    ) {}
}
