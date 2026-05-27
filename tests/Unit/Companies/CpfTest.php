<?php

declare(strict_types=1);

use Src\Companies\Domain\Exceptions\InvalidCpf;
use Src\Companies\Domain\ValueObjects\Cpf;

it('aceita CPF válido', function () {
    $cpf = new Cpf('52998224725');
    expect($cpf->value)->toBe('52998224725');
});

it('lança InvalidCpf para CPF inválido', function () {
    new Cpf('12345678901');
})->throws(InvalidCpf::class);

it('lança InvalidCpf para todos os dígitos iguais', function () {
    new Cpf('11111111111');
})->throws(InvalidCpf::class);

it('formata o CPF corretamente', function () {
    $cpf = new Cpf('52998224725');
    expect($cpf->format())->toBe('529.982.247-25');
});

it('aceita CPF com pontuação e remove os não-dígitos', function () {
    $cpf = new Cpf('529.982.247-25');
    expect($cpf->value)->toBe('52998224725');
});

it('equals retorna true para o mesmo CPF', function () {
    expect((new Cpf('52998224725'))->equals(new Cpf('52998224725')))->toBeTrue();
});

it('equals retorna false para CPFs diferentes', function () {
    expect((new Cpf('52998224725'))->equals(new Cpf('11144477735')))->toBeFalse();
});
