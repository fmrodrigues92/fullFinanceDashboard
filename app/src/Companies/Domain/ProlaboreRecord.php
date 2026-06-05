<?php

declare(strict_types=1);

namespace Src\Companies\Domain;

use DateTimeImmutable;

final class ProlaboreRecord
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $companyId,
        public readonly int $partnerId,
        public readonly int $userId,
        public readonly DateTimeImmutable $competencia,
        public readonly float $valor,
        public readonly ?string $observacao,
        public readonly string $origem, // 'manual' | 'automatico'
    ) {}

    public static function create(
        int $companyId,
        int $partnerId,
        int $userId,
        DateTimeImmutable $competencia,
        float $valor,
        ?string $observacao = null,
        string $origem = 'manual',
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            partnerId: $partnerId,
            userId: $userId,
            competencia: $competencia,
            valor: $valor,
            observacao: $observacao,
            origem: $origem,
        );
    }

    public static function fromPersistence(
        int $id,
        int $companyId,
        int $partnerId,
        int $userId,
        DateTimeImmutable $competencia,
        float $valor,
        ?string $observacao,
        string $origem = 'manual',
    ): self {
        return new self(
            id: $id,
            companyId: $companyId,
            partnerId: $partnerId,
            userId: $userId,
            competencia: $competencia,
            valor: $valor,
            observacao: $observacao,
            origem: $origem,
        );
    }

    public function update(
        DateTimeImmutable $competencia,
        float $valor,
        ?string $observacao,
    ): self {
        return new self(
            id: $this->id,
            companyId: $this->companyId,
            partnerId: $this->partnerId,
            userId: $this->userId,
            competencia: $competencia,
            valor: $valor,
            observacao: $observacao,
            origem: $this->origem, // preserva origem ao editar
        );
    }
}
