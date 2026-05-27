<?php

declare(strict_types=1);

namespace Src\Companies\Domain;

use Src\Companies\Domain\ValueObjects\Cpf;
use Src\Companies\Domain\ValueObjects\ParticipacaoPercentual;

final class CompanyPartner
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $companyId,
        public readonly int $userId,
        public readonly string $nome,
        public readonly Cpf $cpf,
        public readonly ParticipacaoPercentual $participacao,
    ) {}

    public static function create(
        int $companyId,
        int $userId,
        string $nome,
        string $cpf,
        float $participacao,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            userId: $userId,
            nome: $nome,
            cpf: new Cpf($cpf),
            participacao: new ParticipacaoPercentual($participacao),
        );
    }

    public static function fromPersistence(
        int $id,
        int $companyId,
        int $userId,
        string $nome,
        Cpf $cpf,
        ParticipacaoPercentual $participacao,
    ): self {
        return new self(
            id: $id,
            companyId: $companyId,
            userId: $userId,
            nome: $nome,
            cpf: $cpf,
            participacao: $participacao,
        );
    }
}
