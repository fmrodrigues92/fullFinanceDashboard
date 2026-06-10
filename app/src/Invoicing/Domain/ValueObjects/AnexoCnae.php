<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain\ValueObjects;

use Src\Invoicing\Domain\Exceptions\InvalidAnexoCnae;

final readonly class AnexoCnae
{
    public function __construct(public int $value)
    {
        if (! in_array($value, [3, 5], true)) {
            throw new InvalidAnexoCnae("Anexo CNAE deve ser 3 ou 5, recebido: {$value}.");
        }
    }
}
