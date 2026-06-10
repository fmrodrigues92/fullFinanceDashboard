<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\CompanyPartnerModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreConfigModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreRecordModel;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeCompanyWithConfig(int $userId, float $valor = 5000.0): array
{
    $company = CompanyModel::factory()->create(['user_id' => $userId]);
    $partner = CompanyPartnerModel::factory()->create(['company_id' => $company->id, 'user_id' => $userId]);
    $config = ProlaboreConfigModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $userId,
        'valor' => $valor,
    ]);

    return compact('company', 'partner', 'config');
}

function addRealInvoice(int $companyId, string $date, float $valor = 10000.0): void
{
    InvoiceModel::factory()->create([
        'company_id' => $companyId,
        'is_simulation' => false,
        'data_emissao' => $date,
        'valor_brl' => $valor,
        'deleted_at' => null,
    ]);
}

// ─── CA1: só age no dia 1 ────────────────────────────────────────────────────

it('does not create records when today is not the 1st of the month', function () {
    CarbonImmutable::setTestNow('2026-06-15 01:00:00');

    $user = User::factory()->create();
    makeCompanyWithConfig($user->id);

    $this->artisan('prolabore:generate-records')->assertSuccessful();

    expect(ProlaboreRecordModel::count())->toBe(0);
})->after(fn () => CarbonImmutable::setTestNow());

// ─── CA2: cria recibo automático quando há faturamento e não há recibo ────────

it('creates automatic record for previous month when faturamento exists', function () {
    CarbonImmutable::setTestNow('2026-06-01 01:00:00');

    $user = User::factory()->create();
    ['company' => $company, 'partner' => $partner] = makeCompanyWithConfig($user->id, 5000.0);

    addRealInvoice($company->id, '2026-05-15'); // faturamento em maio

    $this->artisan('prolabore:generate-records')->assertSuccessful();

    $record = ProlaboreRecordModel::where('company_id', $company->id)
        ->where('partner_id', $partner->id)
        ->where('competencia', '2026-05-01')
        ->first();

    expect($record)->not->toBeNull()
        ->and((float) $record->valor)->toBe(5000.0)
        ->and($record->origem)->toBe('automatico');
})->after(fn () => CarbonImmutable::setTestNow());

// ─── CA3: não sobrescreve recibo manual existente ─────────────────────────────

it('does not overwrite existing manual record', function () {
    CarbonImmutable::setTestNow('2026-06-01 01:00:00');

    $user = User::factory()->create();
    ['company' => $company, 'partner' => $partner] = makeCompanyWithConfig($user->id, 5000.0);

    addRealInvoice($company->id, '2026-05-10');

    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => '2026-05-01',
        'valor' => 9999.0,
        'origem' => 'manual',
    ]);

    $this->artisan('prolabore:generate-records')->assertSuccessful();

    $records = ProlaboreRecordModel::where('company_id', $company->id)
        ->where('competencia', '2026-05-01')
        ->get();

    expect($records)->toHaveCount(1)
        ->and((float) $records->first()->valor)->toBe(9999.0)
        ->and($records->first()->origem)->toBe('manual');
})->after(fn () => CarbonImmutable::setTestNow());

// ─── CA4: não cria quando não há faturamento ─────────────────────────────────

it('does not create record when there is no real faturamento in previous month', function () {
    CarbonImmutable::setTestNow('2026-06-01 01:00:00');

    $user = User::factory()->create();
    makeCompanyWithConfig($user->id);

    // Sem invoices em maio → nenhum recibo criado

    $this->artisan('prolabore:generate-records')->assertSuccessful();

    expect(ProlaboreRecordModel::count())->toBe(0);
})->after(fn () => CarbonImmutable::setTestNow());

// ─── CA4b: simulações não contam como faturamento para o cronjob ─────────────

it('does not create record when only simulation invoices exist', function () {
    CarbonImmutable::setTestNow('2026-06-01 01:00:00');

    $user = User::factory()->create();
    ['company' => $company] = makeCompanyWithConfig($user->id);

    InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'is_simulation' => true,
        'data_emissao' => '2026-05-10',
        'valor_brl' => 10000,
        'deleted_at' => null,
    ]);

    $this->artisan('prolabore:generate-records')->assertSuccessful();

    expect(ProlaboreRecordModel::count())->toBe(0);
})->after(fn () => CarbonImmutable::setTestNow());

// ─── CA5: origem = 'automatico' ──────────────────────────────────────────────

it('created record has origem automatico', function () {
    CarbonImmutable::setTestNow('2026-06-01 01:00:00');

    $user = User::factory()->create();
    ['company' => $company, 'partner' => $partner] = makeCompanyWithConfig($user->id);

    addRealInvoice($company->id, '2026-05-20');

    $this->artisan('prolabore:generate-records')->assertSuccessful();

    $record = ProlaboreRecordModel::where('company_id', $company->id)->first();
    expect($record->origem)->toBe('automatico');
})->after(fn () => CarbonImmutable::setTestNow());

// ─── dry-run não persiste ─────────────────────────────────────────────────────

it('dry-run flag does not persist any records', function () {
    CarbonImmutable::setTestNow('2026-06-01 01:00:00');

    $user = User::factory()->create();
    ['company' => $company] = makeCompanyWithConfig($user->id);

    addRealInvoice($company->id, '2026-05-10');

    $this->artisan('prolabore:generate-records --dry-run')->assertSuccessful();

    expect(ProlaboreRecordModel::count())->toBe(0);
})->after(fn () => CarbonImmutable::setTestNow());

// ─── Múltiplas empresas/sócios ────────────────────────────────────────────────

it('creates records for multiple companies and partners', function () {
    CarbonImmutable::setTestNow('2026-06-01 01:00:00');

    $user = User::factory()->create();

    ['company' => $c1] = makeCompanyWithConfig($user->id, 3000.0);
    ['company' => $c2] = makeCompanyWithConfig($user->id, 7000.0);

    addRealInvoice($c1->id, '2026-05-05');
    addRealInvoice($c2->id, '2026-05-12');

    $this->artisan('prolabore:generate-records')->assertSuccessful();

    expect(ProlaboreRecordModel::count())->toBe(2)
        ->and(ProlaboreRecordModel::where('company_id', $c1->id)->value('valor'))->toBe('3000.00')
        ->and(ProlaboreRecordModel::where('company_id', $c2->id)->value('valor'))->toBe('7000.00');
})->after(fn () => CarbonImmutable::setTestNow());
