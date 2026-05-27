<?php

declare(strict_types=1);

namespace Src\Companies\Application\DTOs;

use DateTimeImmutable;

final readonly class CreateProlaboreRecordInput
{
    public function __construct(
        public int $companyId,
        public int $partnerId,
        public int $userId,
        public DateTimeImmutable $competencia,
        public float $valor,
        public ?string $observacao = null,
    ) {}
}
