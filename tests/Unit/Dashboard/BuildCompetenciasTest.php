<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Carbon\Carbon;

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Invoca o método privado buildCompetencias() via Reflection.
 *
 * @return string[]
 */
function invokeBuildCompetencias(): array
{
    $controller = new DashboardController;
    $method = new ReflectionMethod(DashboardController::class, 'buildCompetencias');

    return $method->invoke($controller);
}

// ─── Estrutura geral ─────────────────────────────────────────────────────────

it('retorna exatamente 13 competências', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15'));

    $result = invokeBuildCompetencias();

    expect($result)->toHaveCount(13);
})->after(fn () => Carbon::setTestNow());

it('todas as competências seguem o formato YYYY-MM', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15'));

    $result = invokeBuildCompetencias();

    foreach ($result as $comp) {
        expect($comp)->toMatch('/^\d{4}-\d{2}$/');
    }
})->after(fn () => Carbon::setTestNow());

it('primeiro elemento é 6 meses antes do mês atual', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15'));

    $result = invokeBuildCompetencias();

    expect($result[0])->toBe('2025-12');
})->after(fn () => Carbon::setTestNow());

it('último elemento é 6 meses depois do mês atual', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15'));

    $result = invokeBuildCompetencias();

    expect($result[12])->toBe('2026-12');
})->after(fn () => Carbon::setTestNow());

it('o elemento central (índice 6) é o mês atual', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15'));

    $result = invokeBuildCompetencias();

    expect($result[6])->toBe('2026-06');
})->after(fn () => Carbon::setTestNow());

it('competências são contínuas e sem lacunas', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-15'));

    $result = invokeBuildCompetencias();

    for ($i = 1; $i < count($result); $i++) {
        $prev = Carbon::parse($result[$i - 1].'-01');
        $curr = Carbon::parse($result[$i].'-01');
        expect((int) $curr->diffInMonths($prev, absolute: true))->toBe(1);
    }
})->after(fn () => Carbon::setTestNow());

// ─── Bordas de virada de ano ─────────────────────────────────────────────────

it('cruza virada de ano corretamente: -6 meses de janeiro', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10'));

    $result = invokeBuildCompetencias();

    // -6 meses de 2026-01 = 2025-07
    expect($result[0])->toBe('2025-07')
        ->and($result[6])->toBe('2026-01')
        ->and($result[12])->toBe('2026-07');
})->after(fn () => Carbon::setTestNow());

it('cruza virada de ano corretamente: +6 meses de julho', function () {
    Carbon::setTestNow(Carbon::parse('2025-07-20'));

    $result = invokeBuildCompetencias();

    // -6 = 2025-01, +6 = 2026-01
    expect($result[0])->toBe('2025-01')
        ->and($result[6])->toBe('2025-07')
        ->and($result[12])->toBe('2026-01');
})->after(fn () => Carbon::setTestNow());

it('centrado em dezembro gera sequência de dois anos', function () {
    Carbon::setTestNow(Carbon::parse('2026-12-01'));

    $result = invokeBuildCompetencias();

    expect($result[0])->toBe('2026-06')
        ->and($result[6])->toBe('2026-12')
        ->and($result[12])->toBe('2027-06');
})->after(fn () => Carbon::setTestNow());
