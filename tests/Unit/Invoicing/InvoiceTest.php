<?php

declare(strict_types=1);

use Src\Invoicing\Domain\Enums\InvoiceTipo;
use Src\Invoicing\Domain\Exceptions\InternationalInvoiceFieldsRequired;
use Src\Invoicing\Domain\Exceptions\NationalInvoiceShouldNotHaveUsdFields;
use Src\Invoicing\Domain\Invoice;
use Src\Invoicing\Domain\ValueObjects\AnexoCnae;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeAnexo(int $v = 3): AnexoCnae
{
    return new AnexoCnae($v);
}

function makeDate(string $d = '2026-05-01'): DateTimeImmutable
{
    return new DateTimeImmutable($d);
}

// ─── createReal: nota nacional ───────────────────────────────────────────────

it('createReal nacional aceita campos USD nulos', function () {
    $invoice = Invoice::createReal(
        companyId: 1,
        clientId: 10,
        tipo: InvoiceTipo::Nacional,
        anexoCnae: makeAnexo(),
        dataEmissao: makeDate(),
        valorBrl: '10000.00',
        valorUsd: null,
        cotacao: null,
        observacao: null,
    );

    expect($invoice->isSimulation)->toBeFalse()
        ->and($invoice->clientId)->toBe(10)
        ->and($invoice->simulationBatchId)->toBeNull()
        ->and($invoice->tipo)->toBe(InvoiceTipo::Nacional)
        ->and($invoice->id)->toBeNull();
});

it('createReal nacional com valorUsd lança NationalInvoiceShouldNotHaveUsdFields', function () {
    Invoice::createReal(
        companyId: 1,
        clientId: 10,
        tipo: InvoiceTipo::Nacional,
        anexoCnae: makeAnexo(),
        dataEmissao: makeDate(),
        valorBrl: '10000.00',
        valorUsd: '2000.00',
        cotacao: null,
        observacao: null,
    );
})->throws(NationalInvoiceShouldNotHaveUsdFields::class);

it('createReal nacional com cotacao lança NationalInvoiceShouldNotHaveUsdFields', function () {
    Invoice::createReal(
        companyId: 1,
        clientId: 10,
        tipo: InvoiceTipo::Nacional,
        anexoCnae: makeAnexo(),
        dataEmissao: makeDate(),
        valorBrl: '10000.00',
        valorUsd: null,
        cotacao: '5.0000',
        observacao: null,
    );
})->throws(NationalInvoiceShouldNotHaveUsdFields::class);

it('createReal nacional com ambos os campos USD lança NationalInvoiceShouldNotHaveUsdFields', function () {
    Invoice::createReal(
        companyId: 1,
        clientId: 10,
        tipo: InvoiceTipo::Nacional,
        anexoCnae: makeAnexo(),
        dataEmissao: makeDate(),
        valorBrl: '10000.00',
        valorUsd: '2000.00',
        cotacao: '5.0000',
        observacao: null,
    );
})->throws(NationalInvoiceShouldNotHaveUsdFields::class);

// ─── createReal: nota internacional ─────────────────────────────────────────

it('createReal internacional aceita valorUsd e cotacao preenchidos', function () {
    $invoice = Invoice::createReal(
        companyId: 1,
        clientId: 10,
        tipo: InvoiceTipo::Internacional,
        anexoCnae: makeAnexo(5),
        dataEmissao: makeDate(),
        valorBrl: '50000.00',
        valorUsd: '10000.00',
        cotacao: '5.0000',
        observacao: null,
    );

    expect($invoice->tipo)->toBe(InvoiceTipo::Internacional)
        ->and($invoice->valorUsd)->toBe('10000.00')
        ->and($invoice->cotacao)->toBe('5.0000');
});

it('createReal internacional sem valorUsd lança InternationalInvoiceFieldsRequired', function (
    ?string $valorUsd,
    ?string $cotacao,
) {
    Invoice::createReal(
        companyId: 1,
        clientId: 10,
        tipo: InvoiceTipo::Internacional,
        anexoCnae: makeAnexo(),
        dataEmissao: makeDate(),
        valorBrl: '50000.00',
        valorUsd: $valorUsd,
        cotacao: $cotacao,
        observacao: null,
    );
})->with([
    'ambos nulos' => [null, null],
    'valorUsd nulo' => [null, '5.0000'],
    'cotacao nula' => ['10000.00', null],
])->throws(InternationalInvoiceFieldsRequired::class);

// ─── createSimulation ────────────────────────────────────────────────────────

it('createSimulation define clientId nulo, isSimulation true e campos USD nulos', function () {
    $invoice = Invoice::createSimulation(
        companyId: 1,
        simulationBatchId: 42,
        tipo: InvoiceTipo::Nacional,
        anexoCnae: makeAnexo(),
        dataEmissao: makeDate('2026-01-01'),
        valorBrl: '15000.00',
    );

    expect($invoice->clientId)->toBeNull()
        ->and($invoice->simulationBatchId)->toBe(42)
        ->and($invoice->isSimulation)->toBeTrue()
        ->and($invoice->valorUsd)->toBeNull()
        ->and($invoice->cotacao)->toBeNull()
        ->and($invoice->observacao)->toBeNull()
        ->and($invoice->id)->toBeNull();
});
