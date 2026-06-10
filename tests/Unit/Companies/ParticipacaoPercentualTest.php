<?php

declare(strict_types=1);

use Src\Companies\Domain\Exceptions\InvalidParticipacao;
use Src\Companies\Domain\ValueObjects\ParticipacaoPercentual;

it('lança InvalidParticipacao para valor zero', function () {
    new ParticipacaoPercentual(0);
})->throws(InvalidParticipacao::class);

it('aceita 100.00', function () {
    $p = new ParticipacaoPercentual(100.0);
    expect($p->value)->toBe(100.0);
});

it('lança InvalidParticipacao para valor acima de 100', function () {
    new ParticipacaoPercentual(100.01);
})->throws(InvalidParticipacao::class);

it('aceita valor positivo dentro do intervalo', function () {
    $p = new ParticipacaoPercentual(33.33);
    expect($p->value)->toBe(33.33);
});

it('lança InvalidParticipacao para valor negativo', function () {
    new ParticipacaoPercentual(-1.0);
})->throws(InvalidParticipacao::class);
