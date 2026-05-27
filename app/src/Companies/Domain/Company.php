<?php

declare(strict_types=1);

namespace Src\Companies\Domain;

use Src\Companies\Domain\Enums\RegimeTributario;
use Src\Companies\Domain\ValueObjects\Cnpj;

final class Company
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly string $razaoSocial,
        public readonly string $nomeFantasia,
        public readonly Cnpj $cnpj,
        public readonly RegimeTributario $regimeTributario,
    ) {}

    public static function create(
        int $userId,
        string $razaoSocial,
        string $nomeFantasia,
        string $cnpj,
        RegimeTributario $regimeTributario,
    ): self {
        return new self(
            id: null,
            userId: $userId,
            razaoSocial: $razaoSocial,
            nomeFantasia: $nomeFantasia,
            cnpj: new Cnpj($cnpj),
            regimeTributario: $regimeTributario,
        );
    }

    public static function fromPersistence(
        int $id,
        int $userId,
        string $razaoSocial,
        string $nomeFantasia,
        Cnpj $cnpj,
        RegimeTributario $regimeTributario,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            razaoSocial: $razaoSocial,
            nomeFantasia: $nomeFantasia,
            cnpj: $cnpj,
            regimeTributario: $regimeTributario,
        );
    }

    public function update(
        string $razaoSocial,
        string $nomeFantasia,
        string $cnpj,
        RegimeTributario $regimeTributario,
    ): self {
        return new self(
            id: $this->id,
            userId: $this->userId,
            razaoSocial: $razaoSocial,
            nomeFantasia: $nomeFantasia,
            cnpj: new Cnpj($cnpj),
            regimeTributario: $regimeTributario,
        );
    }
}
