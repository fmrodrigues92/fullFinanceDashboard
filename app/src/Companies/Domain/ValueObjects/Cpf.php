<?php

declare(strict_types=1);

namespace Src\Companies\Domain\ValueObjects;

use Src\Companies\Domain\Exceptions\InvalidCpf;

final readonly class Cpf
{
    public string $value;

    public function __construct(string $raw)
    {
        $digits = preg_replace('/\D/', '', $raw);

        if (! self::isValid((string) $digits)) {
            throw new InvalidCpf("CPF inválido: {$raw}");
        }

        $this->value = (string) $digits;
    }

    private static function isValid(string $digits): bool
    {
        if (strlen($digits) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $digits[$i] * (10 - $i);
        }
        $rem = ($sum * 10) % 11;
        $d1 = ($rem === 10 || $rem === 11) ? 0 : $rem;

        if ((int) $digits[9] !== $d1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $digits[$i] * (11 - $i);
        }
        $rem = ($sum * 10) % 11;
        $d2 = ($rem === 10 || $rem === 11) ? 0 : $rem;

        return (int) $digits[10] === $d2;
    }

    public function format(): string
    {
        return vsprintf(
            '%s%s%s.%s%s%s.%s%s%s-%s%s',
            str_split($this->value),
        );
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
