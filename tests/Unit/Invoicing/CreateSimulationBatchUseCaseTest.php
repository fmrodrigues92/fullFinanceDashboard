<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Invoicing\Application\DTOs\CreateSimulationBatchInput;
use Src\Invoicing\Application\TransactionManager;
use Src\Invoicing\Application\UseCases\SimulationBatch\CreateSimulationBatchUseCase;
use Src\Invoicing\Domain\Exceptions\SimulationConflict;
use Src\Invoicing\Domain\Invoice;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;
use Src\Invoicing\Domain\Repositories\SimulationBatchRepository;
use Src\Invoicing\Domain\SimulationBatch;

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    /** @var MockInterface&SimulationBatchRepository */
    $this->batchRepo = Mockery::mock(SimulationBatchRepository::class);
    /** @var MockInterface&InvoiceRepository */
    $this->invoiceRepo = Mockery::mock(InvoiceRepository::class);
    /** @var MockInterface&TransactionManager */
    $this->txManager = Mockery::mock(TransactionManager::class);
    $this->txManager->shouldReceive('run')->andReturnUsing(fn (callable $cb) => $cb());

    $this->useCase = new CreateSimulationBatchUseCase($this->batchRepo, $this->invoiceRepo, $this->txManager);

    $this->savedBatch = SimulationBatch::fromPersistence(id: 7, companyId: 1);
});

afterEach(fn () => Mockery::close());

// ─── Geração de meses ────────────────────────────────────────────────────────

it('mês único gera exatamente 1 simulação com data no primeiro dia', function () {
    $this->invoiceRepo->shouldReceive('conflictingSimulationDates')->andReturn([]);
    $this->batchRepo->shouldReceive('save')->andReturn($this->savedBatch);

    $captured = [];
    $this->invoiceRepo->shouldReceive('insertMany')
        ->once()
        ->withArgs(function (array $invoices) use (&$captured) {
            $captured = $invoices;

            return true;
        });

    ($this->useCase)(new CreateSimulationBatchInput(
        companyId: 1,
        tipo: 'nacional',
        anexoCnae: 3,
        dataInicio: '2026-06',
        dataTermino: '2026-06',
        valorBrl: '10000.00',
    ));

    expect($captured)->toHaveCount(1)
        ->and($captured[0]->dataEmissao->format('Y-m-d'))->toBe('2026-06-01');
});

it('intervalo de 3 meses gera 3 simulações com datas nos primeiros dias', function () {
    $this->invoiceRepo->shouldReceive('conflictingSimulationDates')->andReturn([]);
    $this->batchRepo->shouldReceive('save')->andReturn($this->savedBatch);

    $captured = [];
    $this->invoiceRepo->shouldReceive('insertMany')
        ->once()
        ->withArgs(function (array $invoices) use (&$captured) {
            $captured = $invoices;

            return true;
        });

    ($this->useCase)(new CreateSimulationBatchInput(
        companyId: 1,
        tipo: 'nacional',
        anexoCnae: 3,
        dataInicio: '2026-01',
        dataTermino: '2026-03',
        valorBrl: '15000.00',
    ));

    expect($captured)->toHaveCount(3);

    $dates = array_map(fn (Invoice $i) => $i->dataEmissao->format('Y-m-d'), $captured);
    expect($dates)->toBe(['2026-01-01', '2026-02-01', '2026-03-01']);
});

it('intervalo cruzando virada de ano gera meses corretos', function () {
    $this->invoiceRepo->shouldReceive('conflictingSimulationDates')->andReturn([]);
    $this->batchRepo->shouldReceive('save')->andReturn($this->savedBatch);

    $captured = [];
    $this->invoiceRepo->shouldReceive('insertMany')
        ->once()
        ->withArgs(function (array $invoices) use (&$captured) {
            $captured = $invoices;

            return true;
        });

    ($this->useCase)(new CreateSimulationBatchInput(
        companyId: 1,
        tipo: 'nacional',
        anexoCnae: 3,
        dataInicio: '2025-11',
        dataTermino: '2026-02',
        valorBrl: '10000.00',
    ));

    expect($captured)->toHaveCount(4);

    $dates = array_map(fn (Invoice $i) => $i->dataEmissao->format('Y-m-d'), $captured);
    expect($dates)->toBe(['2025-11-01', '2025-12-01', '2026-01-01', '2026-02-01']);
});

it('todas as simulações geradas têm isSimulation true e o companyId correto', function () {
    $this->invoiceRepo->shouldReceive('conflictingSimulationDates')->andReturn([]);
    $this->batchRepo->shouldReceive('save')->andReturn($this->savedBatch);

    $captured = [];
    $this->invoiceRepo->shouldReceive('insertMany')
        ->once()
        ->withArgs(function (array $invoices) use (&$captured) {
            $captured = $invoices;

            return true;
        });

    ($this->useCase)(new CreateSimulationBatchInput(
        companyId: 1,
        tipo: 'nacional',
        anexoCnae: 3,
        dataInicio: '2026-01',
        dataTermino: '2026-02',
        valorBrl: '5000.00',
    ));

    foreach ($captured as $invoice) {
        expect($invoice->isSimulation)->toBeTrue()
            ->and($invoice->companyId)->toBe(1)
            ->and($invoice->clientId)->toBeNull();
    }
});

// ─── Detecção de conflito ────────────────────────────────────────────────────

it('lança SimulationConflict com os meses conflitantes quando há colisão', function () {
    $this->invoiceRepo->shouldReceive('conflictingSimulationDates')
        ->andReturn(['2026-02-01']);

    $this->batchRepo->shouldNotReceive('save');
    $this->invoiceRepo->shouldNotReceive('insertMany');

    try {
        ($this->useCase)(new CreateSimulationBatchInput(
            companyId: 1,
            tipo: 'nacional',
            anexoCnae: 3,
            dataInicio: '2026-01',
            dataTermino: '2026-03',
            valorBrl: '10000.00',
        ));

        throw new RuntimeException('Expected SimulationConflict not thrown');
    } catch (SimulationConflict $e) {
        expect($e->conflictingMonths)->toContain('2026-02-01');
    }
});

it('múltiplos conflitos são todos reportados', function () {
    $this->invoiceRepo->shouldReceive('conflictingSimulationDates')
        ->andReturn(['2026-01-01', '2026-03-01']);

    $this->batchRepo->shouldNotReceive('save');
    $this->invoiceRepo->shouldNotReceive('insertMany');

    try {
        ($this->useCase)(new CreateSimulationBatchInput(
            companyId: 1,
            tipo: 'nacional',
            anexoCnae: 3,
            dataInicio: '2026-01',
            dataTermino: '2026-03',
            valorBrl: '10000.00',
        ));

        throw new RuntimeException('Expected SimulationConflict not thrown');
    } catch (SimulationConflict $e) {
        expect($e->conflictingMonths)->toHaveCount(2)
            ->toContain('2026-01-01')
            ->toContain('2026-03-01');
    }
});

it('sem conflitos o lote é salvo e retornado', function () {
    $this->invoiceRepo->shouldReceive('conflictingSimulationDates')->andReturn([]);
    $this->batchRepo->shouldReceive('save')->once()->andReturn($this->savedBatch);
    $this->invoiceRepo->shouldReceive('insertMany')->once();

    $result = ($this->useCase)(new CreateSimulationBatchInput(
        companyId: 1,
        tipo: 'nacional',
        anexoCnae: 3,
        dataInicio: '2026-01',
        dataTermino: '2026-01',
        valorBrl: '10000.00',
    ));

    expect($result->id)->toBe(7)
        ->and($result->companyId)->toBe(1);
});
