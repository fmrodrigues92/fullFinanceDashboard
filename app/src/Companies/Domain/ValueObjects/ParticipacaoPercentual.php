<?php

declare(strict_types=1);

namespace Src\Companies\Domain\ValueObjects;

use Src\Companies\Domain\Exceptions\InvalidParticipacao;

final readonly class ParticipacaoPercentual
{
    public function __construct(public float $value)
    {
        if ($value <= 0 || $value > 100) {
            throw new InvalidParticipacao(
                "Participação deve ser > 0 e ≤ 100, recebido: {$value}",
            );
        }
    }
}
