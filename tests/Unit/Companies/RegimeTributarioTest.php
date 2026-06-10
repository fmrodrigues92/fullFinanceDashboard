<?php

declare(strict_types=1);

use Src\Companies\Domain\Enums\RegimeTributario;

// ─── Labels ─────────────────────────────────────────────────────────────────

it('retorna o label correto para cada regime tributário', function (RegimeTributario $regime, string $expected) {
    expect($regime->label())->toBe($expected);
})->with([
    'MEI' => [RegimeTributario::MEI, 'MEI'],
    'Simples Nacional' => [RegimeTributario::SimplesNacional, 'Simples Nacional'],
    'Lucro Presumido' => [RegimeTributario::LucroPresumido, 'Lucro Presumido'],
    'Lucro Real' => [RegimeTributario::LucroReal, 'Lucro Real'],
]);

// ─── Criação a partir de string ──────────────────────────────────────────────

it('pode ser criado a partir do valor string', function (string $value, RegimeTributario $expected) {
    expect(RegimeTributario::from($value))->toBe($expected);
})->with([
    'mei' => ['mei', RegimeTributario::MEI],
    'simples_nacional' => ['simples_nacional', RegimeTributario::SimplesNacional],
    'lucro_presumido' => ['lucro_presumido', RegimeTributario::LucroPresumido],
    'lucro_real' => ['lucro_real', RegimeTributario::LucroReal],
]);

it('lança ValueError para string inválida', function () {
    RegimeTributario::from('invalido');
})->throws(ValueError::class);
