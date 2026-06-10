<?php

declare(strict_types=1);

use Src\Companies\Domain\Exceptions\InvalidCnpj;
use Src\Companies\Domain\ValueObjects\Cnpj;

it('aceita CNPJ válido', function () {
    $cnpj = new Cnpj('11222333000181');
    expect($cnpj->value)->toBe('11222333000181');
});

it('lança InvalidCnpj para CNPJ inválido', function () {
    new Cnpj('12345678000100');
})->throws(InvalidCnpj::class);

it('lança InvalidCnpj para todos os dígitos iguais', function () {
    new Cnpj('11111111111111');
})->throws(InvalidCnpj::class);

it('formata o CNPJ corretamente', function () {
    $cnpj = new Cnpj('11222333000181');
    expect($cnpj->format())->toBe('11.222.333/0001-81');
});

it('aceita CNPJ com pontuação e remove os não-dígitos', function () {
    $cnpj = new Cnpj('11.222.333/0001-81');
    expect($cnpj->value)->toBe('11222333000181');
});

it('equals retorna true para o mesmo CNPJ', function () {
    expect((new Cnpj('11222333000181'))->equals(new Cnpj('11222333000181')))->toBeTrue();
});

it('equals retorna false para CNPJs diferentes', function () {
    expect((new Cnpj('11222333000181'))->equals(new Cnpj('11444777000161')))->toBeFalse();
});
