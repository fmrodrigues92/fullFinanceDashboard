<?php

declare(strict_types=1);

namespace Src\Companies\Domain;

final class ProlaboreConfig
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $companyId,
        public readonly int $partnerId,
        public readonly int $userId,
        public readonly float $valor,
    ) {}

    public static function create(
        int $companyId,
        int $partnerId,
        int $userId,
        float $valor,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            partnerId: $partnerId,
            userId: $userId,
            valor: $valor,
        );
    }

    public static function fromPersistence(
        int $id,
        int $companyId,
        int $partnerId,
        int $userId,
        float $valor,
    ): self {
        return new self(
            id: $id,
            companyId: $companyId,
            partnerId: $partnerId,
            userId: $userId,
            valor: $valor,
        );
    }

    public function update(float $valor): self
    {
        return new self(
            id: $this->id,
            companyId: $this->companyId,
            partnerId: $this->partnerId,
            userId: $this->userId,
            valor: $valor,
        );
    }
}
