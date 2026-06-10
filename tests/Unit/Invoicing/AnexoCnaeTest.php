<?php

declare(strict_types=1);

use Src\Invoicing\Domain\Exceptions\InvalidAnexoCnae;
use Src\Invoicing\Domain\ValueObjects\AnexoCnae;

// ─── Valores válidos ─────────────────────────────────────────────────────────

it('aceita anexo 3', function () {
    $vo = new AnexoCnae(3);
    expect($vo->value)->toBe(3);
});

it('aceita anexo 5', function () {
    $vo = new AnexoCnae(5);
    expect($vo->value)->toBe(5);
});

// ─── Valores inválidos ───────────────────────────────────────────────────────

it('rejeita valores fora de {3,5}', function (int $invalid) {
    new AnexoCnae($invalid);
})->with([0, 1, 2, 4, 6, -1, 100])->throws(InvalidAnexoCnae::class);
