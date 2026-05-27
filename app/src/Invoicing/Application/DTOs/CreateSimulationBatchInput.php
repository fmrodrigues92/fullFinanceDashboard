<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\DTOs;

final readonly class CreateSimulationBatchInput
{
    public function __construct(
        public int $companyId,
        public string $tipo,
        public int $anexoCnae,
        public string $dataInicio,
        public string $dataTermino,
        public string $valorBrl,
    ) {}
}
