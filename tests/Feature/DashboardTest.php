<?php

declare(strict_types=1);

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\CompanyPartnerModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreConfigModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreRecordModel;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;

// ─── Auth ────────────────────────────────────────────────────────────────────

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

// ─── Payload shape ────────────────────────────────────────────────────────────

test('dashboard response contains companies, faturamentoPorEmpresa and prolaborePorEmpresa', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson(route('dashboard'));
    $response->assertOk()
        ->assertJsonStructure(['companies', 'faturamentoPorEmpresa', 'prolaborePorEmpresa']);
});

// ─── Isolamento entre usuários ────────────────────────────────────────────────

test('each user only sees their own companies in prolaborePorEmpresa', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $company1 = CompanyModel::factory()->create(['user_id' => $user1->id, 'regime_tributario' => 'simples_nacional']);
    CompanyModel::factory()->create(['user_id' => $user2->id]);

    $this->actingAs($user1);
    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    // JSON numeric keys decode as integers in PHP — use toHaveKey for portability
    expect($response->json('prolaborePorEmpresa'))->toHaveKey($company1->id);
});

// ─── Pró-labore real: recibo manual ──────────────────────────────────────────

test('past month with manual record returns recibo_manual tipo', function () {
    $user = User::factory()->create();
    $company = CompanyModel::factory()->create(['user_id' => $user->id, 'regime_tributario' => 'lucro_presumido']);
    $partner = CompanyPartnerModel::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

    $pastMonth = now()->subMonth()->startOfMonth()->format('Y-m');
    $competenciaDate = now()->subMonth()->startOfMonth()->format('Y-m-d');

    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $competenciaDate,
        'valor' => 5000.00,
        'origem' => 'manual',
    ]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    $data = $response->json("prolaborePorEmpresa.{$company->id}.{$pastMonth}");
    expect($data['tipo'])->toBe('recibo_manual')
        ->and((float) $data['total'])->toBe(5000.0)
        ->and($data['socios'][0]['tipo'])->toBe('recibo_manual');
});

// ─── Pró-labore real: recibo automático ──────────────────────────────────────

test('past month with automatic record returns recibo_automatico tipo', function () {
    $user = User::factory()->create();
    $company = CompanyModel::factory()->create(['user_id' => $user->id, 'regime_tributario' => 'lucro_presumido']);
    $partner = CompanyPartnerModel::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

    $pastMonth = now()->subMonth()->startOfMonth()->format('Y-m');

    ProlaboreRecordModel::factory()->automatico()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'valor' => 3000.00,
    ]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $data = $response->json("prolaborePorEmpresa.{$company->id}.{$pastMonth}");

    expect($data['tipo'])->toBe('recibo_automatico')
        ->and((float) $data['total'])->toBe(3000.0);
});

// ─── Pró-labore: sem faturamento ─────────────────────────────────────────────

test('past month with no faturamento returns sem_faturamento', function () {
    $user = User::factory()->create();
    $company = CompanyModel::factory()->create(['user_id' => $user->id, 'regime_tributario' => 'lucro_presumido']);
    $partner = CompanyPartnerModel::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    ProlaboreConfigModel::factory()->create(['company_id' => $company->id, 'partner_id' => $partner->id, 'user_id' => $user->id]);

    $twoMonthsAgo = now()->subMonths(2)->startOfMonth()->format('Y-m');

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $data = $response->json("prolaborePorEmpresa.{$company->id}.{$twoMonthsAgo}");

    expect($data['tipo'])->toBe('sem_faturamento')
        ->and((float) $data['total'])->toBe(0.0)
        ->and($data['socios'])->toBeEmpty();
});

// ─── Fator R ─────────────────────────────────────────────────────────────────

test('fator_r is null for non-simples companies', function () {
    $user = User::factory()->create();
    $company = CompanyModel::factory()->create(['user_id' => $user->id, 'regime_tributario' => 'lucro_presumido']);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $currentMonth = now()->format('Y-m');

    $fatorR = $response->json("prolaborePorEmpresa.{$company->id}.{$currentMonth}.fator_r");
    expect($fatorR)->toBeNull();
});

test('fator_r is present for simples nacional company with revenue history', function () {
    $user = User::factory()->create();
    $company = CompanyModel::factory()->create(['user_id' => $user->id, 'regime_tributario' => 'simples_nacional']);
    $partner = CompanyPartnerModel::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    ProlaboreConfigModel::factory()->create(['company_id' => $company->id, 'partner_id' => $partner->id, 'user_id' => $user->id, 'valor' => 5000]);

    // Criar faturamento e recibos no mês anterior (janela do Fator R)
    $lastMonth = now()->subMonth()->startOfMonth()->format('Y-m-d');
    InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'is_simulation' => false,
        'data_emissao' => $lastMonth,
        'valor_brl' => 20000,
        'deleted_at' => null,
    ]);
    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $lastMonth,
        'valor' => 5000,
        'origem' => 'manual',
    ]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $currentMonth = now()->format('Y-m');

    $fatorR = $response->json("prolaborePorEmpresa.{$company->id}.{$currentMonth}.fator_r");
    expect($fatorR)->not->toBeNull()
        ->and($fatorR['percentual'])->toBeFloat()
        ->and($fatorR)->toHaveKeys(['percentual', 'dentro', 'estimado', 'rbt12', 'folha12']);
});

test('fator_r dentro is true when folha12/rbt12 >= 0.28', function () {
    $user = User::factory()->create();
    $company = CompanyModel::factory()->create(['user_id' => $user->id, 'regime_tributario' => 'simples_nacional']);
    $partner = CompanyPartnerModel::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

    // Prolabore = 30% de faturamento → dentro do Fator R
    $lastMonth = now()->subMonth()->startOfMonth()->format('Y-m-d');
    InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'is_simulation' => false,
        'data_emissao' => $lastMonth,
        'valor_brl' => 10000,
        'deleted_at' => null,
    ]);
    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $lastMonth,
        'valor' => 3000, // 30% de 10000
        'origem' => 'manual',
    ]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $currentMonth = now()->format('Y-m');

    $fatorR = $response->json("prolaborePorEmpresa.{$company->id}.{$currentMonth}.fator_r");
    expect($fatorR['dentro'])->toBeTrue()
        ->and($fatorR['percentual'])->toBeGreaterThanOrEqual(0.28);
});

test('fator_r dentro is false when folha12/rbt12 < 0.28', function () {
    $user = User::factory()->create();
    $company = CompanyModel::factory()->create(['user_id' => $user->id, 'regime_tributario' => 'simples_nacional']);
    $partner = CompanyPartnerModel::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

    // Prolabore = 10% de faturamento → fora do Fator R
    $lastMonth = now()->subMonth()->startOfMonth()->format('Y-m-d');
    InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'is_simulation' => false,
        'data_emissao' => $lastMonth,
        'valor_brl' => 50000,
        'deleted_at' => null,
    ]);
    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $lastMonth,
        'valor' => 5000, // 10% de 50000
        'origem' => 'manual',
    ]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $currentMonth = now()->format('Y-m');

    $fatorR = $response->json("prolaborePorEmpresa.{$company->id}.{$currentMonth}.fator_r");
    expect($fatorR['dentro'])->toBeFalse()
        ->and($fatorR['percentual'])->toBeLessThan(0.28);
});
