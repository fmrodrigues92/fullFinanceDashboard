<?php

declare(strict_types=1);

namespace Src\Companies\Application\DTOs;

use DateTimeImmutable;

final readonly class UpdateProlaboreRecordInput
{
    public function __construct(
        public int $recordId,
        public int $companyId,
        public DateTimeImmutable $competencia,
        public float $valor,
        public ?string $observacao = null,
    ) {}
}
