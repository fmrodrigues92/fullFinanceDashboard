<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Invoicing\Application\UseCases\Invoice\GetFaturamentoDashboardUseCase;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    /** @var MockInterface&InvoiceRepository */
    $this->repo = Mockery::mock(InvoiceRepository::class);

    $this->useCase = new GetFaturamentoDashboardUseCase($this->repo);
});

afterEach(fn () => Mockery::close());

// ─── Delegação ao repository ─────────────────────────────────────────────────

it('delega ao repository com companyId e competencias corretos', function () {
    $companyId = 42;
    $competencias = ['2026-01', '2026-02', '2026-03'];

    $expected = [
        '2026-01' => ['total' => 5000.0, 'notas_emitidas' => 2, 'itens' => [
            ['tipo' => 'nacional', 'valor' => 5000.0, 'quantidade' => 2],
        ]],
        '2026-02' => ['total' => 0.0, 'notas_emitidas' => 0, 'itens' => []],
        '2026-03' => ['total' => 0.0, 'notas_emitidas' => 0, 'itens' => []],
    ];

    $this->repo
        ->shouldReceive('faturamentoPorCompetencias')
        ->once()
        ->with($companyId, $competencias)
        ->andReturn($expected);

    $result = ($this->useCase)($companyId, $competencias);

    expect($result)->toBe($expected);
});

it('devolve o resultado intacto do repository sem transformação', function () {
    $rawResult = [
        '2025-12' => ['total' => 12345.67, 'notas_emitidas' => 7, 'itens' => [
            ['tipo' => 'internacional', 'valor' => 12345.67, 'quantidade' => 7],
        ]],
    ];

    $this->repo
        ->shouldReceive('faturamentoPorCompetencias')
        ->andReturn($rawResult);

    $result = ($this->useCase)(1, ['2025-12']);

    expect($result)->toBe($rawResult);
});

it('aceita lista vazia de competencias e repassa ao repository', function () {
    $this->repo
        ->shouldReceive('faturamentoPorCompetencias')
        ->once()
        ->with(1, [])
        ->andReturn([]);

    $result = ($this->useCase)(1, []);

    expect($result)->toBe([]);
});

it('chama o repository exatamente uma vez por invocação', function () {
    $this->repo
        ->shouldReceive('faturamentoPorCompetencias')
        ->once()
        ->andReturn([]);

    ($this->useCase)(99, ['2026-06']);
});

it('isola companyIds distintos — não mistura resultados entre empresas', function () {
    $resultEmpresa1 = ['2026-01' => ['total' => 1000.0, 'notas_emitidas' => 1, 'itens' => [
        ['tipo' => 'nacional', 'valor' => 1000.0, 'quantidade' => 1],
    ]]];
    $resultEmpresa2 = ['2026-01' => ['total' => 9999.0, 'notas_emitidas' => 5, 'itens' => [
        ['tipo' => 'internacional', 'valor' => 9999.0, 'quantidade' => 5],
    ]]];

    $this->repo
        ->shouldReceive('faturamentoPorCompetencias')
        ->with(1, ['2026-01'])
        ->once()
        ->andReturn($resultEmpresa1);

    $this->repo
        ->shouldReceive('faturamentoPorCompetencias')
        ->with(2, ['2026-01'])
        ->once()
        ->andReturn($resultEmpresa2);

    expect(($this->useCase)(1, ['2026-01']))->toBe($resultEmpresa1)
        ->and(($this->useCase)(2, ['2026-01']))->toBe($resultEmpresa2);
});
