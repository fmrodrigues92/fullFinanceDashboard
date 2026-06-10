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
        public readonly string $tipo, // 'fixo' | 'percentual'
    ) {}

    public static function create(
        int $companyId,
        int $partnerId,
        int $userId,
        float $valor,
        string $tipo = 'fixo',
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            partnerId: $partnerId,
            userId: $userId,
            valor: $valor,
            tipo: $tipo,
        );
    }

    public static function fromPersistence(
        int $id,
        int $companyId,
        int $partnerId,
        int $userId,
        float $valor,
        string $tipo = 'fixo',
    ): self {
        return new self(
            id: $id,
            companyId: $companyId,
            partnerId: $partnerId,
            userId: $userId,
            valor: $valor,
            tipo: $tipo,
        );
    }

    public function update(float $valor, string $tipo): self
    {
        return new self(
            id: $this->id,
            companyId: $this->companyId,
            partnerId: $this->partnerId,
            userId: $this->userId,
            valor: $valor,
            tipo: $tipo,
        );
    }
}
