<?php

declare(strict_types=1);

namespace Src\Companies\Domain\ValueObjects;

use Src\Companies\Domain\Exceptions\InvalidCnpj;

final readonly class Cnpj
{
    public string $value;

    public function __construct(string $raw)
    {
        $digits = preg_replace('/\D/', '', $raw);

        if (! self::isValid((string) $digits)) {
            throw new InvalidCnpj("CNPJ inválido: {$raw}");
        }

        $this->value = (string) $digits;
    }

    private static function isValid(string $digits): bool
    {
        if (strlen($digits) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * $weights1[$i];
        }
        $rem = $sum % 11;
        $d1 = $rem < 2 ? 0 : 11 - $rem;

        if ((int) $digits[12] !== $d1) {
            return false;
        }

        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $digits[$i] * $weights2[$i];
        }
        $rem = $sum % 11;
        $d2 = $rem < 2 ? 0 : 11 - $rem;

        return (int) $digits[13] === $d2;
    }

    public function format(): string
    {
        return vsprintf(
            '%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s',
            str_split($this->value),
        );
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
