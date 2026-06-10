<?php

declare(strict_types=1);

namespace Src\Companies\Domain\Enums;

enum RegimeTributario: string
{
    case MEI = 'mei';
    case SimplesNacional = 'simples_nacional';
    case LucroPresumido = 'lucro_presumido';
    case LucroReal = 'lucro_real';

    public function label(): string
    {
        return match ($this) {
            self::MEI => 'MEI',
            self::SimplesNacional => 'Simples Nacional',
            self::LucroPresumido => 'Lucro Presumido',
            self::LucroReal => 'Lucro Real',
        };
    }
}
