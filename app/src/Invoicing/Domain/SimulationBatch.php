<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain;

final class SimulationBatch
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $companyId,
    ) {}

    public static function create(int $companyId): self
    {
        return new self(null, $companyId);
    }

    public static function fromPersistence(int $id, int $companyId): self
    {
        return new self($id, $companyId);
    }
}
