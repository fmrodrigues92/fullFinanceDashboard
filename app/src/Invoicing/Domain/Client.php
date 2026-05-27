<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain;

final class Client
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $companyId,
        public readonly string $nome,
        public readonly ?string $extId,
    ) {}

    public static function create(int $companyId, string $nome, ?string $extId): self
    {
        return new self(null, $companyId, $nome, $extId);
    }

    public static function fromPersistence(int $id, int $companyId, string $nome, ?string $extId): self
    {
        return new self($id, $companyId, $nome, $extId);
    }
}
