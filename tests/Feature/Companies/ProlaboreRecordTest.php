<?php

declare(strict_types=1);

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\CompanyPartnerModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreRecordModel;

function makeCompanyWithPartnerForRecord(int $userId, string $cnpj = '11222333000181'): array
{
    $company = CompanyModel::factory()->create(['user_id' => $userId, 'cnpj' => $cnpj]);
    $partner = CompanyPartnerModel::factory()->create([
        'company_id' => $company->id,
        'user_id' => $userId,
        'cpf' => '52998224725',
    ]);

    return [$company, $partner];
}

// ─── CA16: registra recibo ───────────────────────────────────────────────────

it('registra recibo de pró-labore e retorna id gerado', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $currentMonth = now()->format('Y-m');

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/prolabore-records", [
            'partner_id' => $partner->id,
            'competencia' => $currentMonth,
            'valor' => 3000.00,
            'observacao' => 'Pagamento mês corrente',
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'partner_id', 'competencia', 'valor', 'origem'])
        ->assertJsonPath('origem', 'manual');

    $this->assertDatabaseHas('prolabore_records', [
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'competencia' => now()->format('Y-m').'-01',
    ]);
});

// ─── CA17: recibo duplicado retorna 422 ─────────────────────────────────────

it('rejeita recibo duplicado para o mesmo sócio, empresa e competência', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $payload = [
        'partner_id' => $partner->id,
        'competencia' => now()->format('Y-m'),
        'valor' => 3000.00,
    ];

    $this->actingAs($user)->postJson("/companies/{$company->id}/prolabore-records", $payload);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/prolabore-records", $payload)
        ->assertUnprocessable();
});

// ─── CA18: sócio de outra empresa retorna 422 ───────────────────────────────

it('rejeita recibo quando sócio não pertence à empresa', function () {
    $user = User::factory()->create();
    [$companyA] = makeCompanyWithPartnerForRecord($user->id, '11222333000181');
    [$companyB, $partnerOfB] = makeCompanyWithPartnerForRecord($user->id, '45539765000163');

    $this->actingAs($user)
        ->postJson("/companies/{$companyA->id}/prolabore-records", [
            'partner_id' => $partnerOfB->id,
            'competencia' => now()->format('Y-m'),
            'valor' => 3000.00,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['partner_id']);
});

// ─── CA16b: POST com mês passado retorna 422 ────────────────────────────────

it('rejeita POST com competencia de mês passado', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $pastMonth = now()->subMonth()->format('Y-m');

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/prolabore-records", [
            'partner_id' => $partner->id,
            'competencia' => $pastMonth,
            'valor' => 3000.00,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['competencia']);
});

// ─── CA19: edita valor e observação ─────────────────────────────────────────

it('edita valor e observação de um recibo existente', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $currentMonth = now()->format('Y-m');
    $currentMonthDate = now()->format('Y-m').'-01';

    $record = ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $currentMonthDate,
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/prolabore-records/{$record->id}", [
            'competencia' => $currentMonth,
            'valor' => 3500.00,
            'observacao' => 'Atualizado',
        ])
        ->assertOk()
        ->assertJsonPath('valor', 3500)
        ->assertJsonPath('origem', 'manual');
});

// ─── SEC-01: PUT em record histórico retorna 422 mesmo com payload do mês corrente ──

it('rejeita PUT em record histórico mesmo que o payload traga o mês corrente', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $pastMonthDate = now()->subMonth()->format('Y-m').'-01';

    $record = ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $pastMonthDate,
        'valor' => 3000.00,
    ]);

    // Payload traz mês corrente (passaria no Form Request), mas o record é histórico
    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/prolabore-records/{$record->id}", [
            'competencia' => now()->format('Y-m'),
            'valor' => 3500.00,
        ])
        ->assertUnprocessable();
});

// ─── CA19b: PUT com mês passado retorna 422 ─────────────────────────────────

it('rejeita PUT com competencia de mês passado', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $pastMonthDate = now()->subMonth()->format('Y-m').'-01';
    $pastMonth = now()->subMonth()->format('Y-m');

    $record = ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $pastMonthDate,
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/prolabore-records/{$record->id}", [
            'competencia' => $pastMonth,
            'valor' => 3500.00,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['competencia']);
});

// ─── CA19c: update força origem = manual ────────────────────────────────────

it('update força origem para manual independente do valor original', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $currentMonth = now()->format('Y-m');
    $currentMonthDate = now()->format('Y-m').'-01';

    $record = ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $currentMonthDate,
        'valor' => 3000.00,
        'origem' => 'automatico',
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/prolabore-records/{$record->id}", [
            'competencia' => $currentMonth,
            'valor' => 4000.00,
        ])
        ->assertOk()
        ->assertJsonPath('origem', 'manual');

    $this->assertDatabaseHas('prolabore_records', [
        'id' => $record->id,
        'origem' => 'manual',
    ]);
});

// ─── CA20: filtro por competência ───────────────────────────────────────────

it('filtra recibos por competência', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => '2026-04-01',
        'valor' => 2000.00,
    ]);
    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => '2026-05-01',
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->getJson("/companies/{$company->id}/prolabore-records?competencia=2026-05")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.competencia', '2026-05-01');
});

it('retorna todos os recibos sem filtro', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    foreach (['2026-03-01', '2026-04-01', '2026-05-01'] as $competencia) {
        ProlaboreRecordModel::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'user_id' => $user->id,
            'competencia' => $competencia,
        ]);
    }

    $this->actingAs($user)
        ->getJson("/companies/{$company->id}/prolabore-records")
        ->assertOk()
        ->assertJsonCount(3);
});

// ─── CA21: exclusão do sócio remove recibos em cascata ──────────────────────

it('remove recibos em cascata ao excluir o sócio', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => '2026-05-01',
        'valor' => 3000.00,
    ]);

    $this->assertDatabaseCount('prolabore_records', 1);

    $partner->delete();

    $this->assertDatabaseCount('prolabore_records', 0);
});

// ─── Bug 3: pertencimento em rotas aninhadas ─────────────────────────────────

it('retorna 404 ao tentar atualizar recibo de outra empresa', function () {
    $user = User::factory()->create();
    [$companyA] = makeCompanyWithPartnerForRecord($user->id, '11222333000181');
    [$companyB, $partnerOfB] = makeCompanyWithPartnerForRecord($user->id, '45539765000163');

    $recordOfB = ProlaboreRecordModel::factory()->create([
        'company_id' => $companyB->id,
        'partner_id' => $partnerOfB->id,
        'user_id' => $user->id,
        'competencia' => now()->format('Y-m').'-01',
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$companyA->id}/prolabore-records/{$recordOfB->id}", [
            'competencia' => now()->format('Y-m'),
            'valor' => 4000.00,
        ])
        ->assertNotFound();
});

it('retorna 404 ao tentar excluir recibo de outra empresa', function () {
    $user = User::factory()->create();
    [$companyA] = makeCompanyWithPartnerForRecord($user->id, '11222333000181');
    [$companyB, $partnerOfB] = makeCompanyWithPartnerForRecord($user->id, '45539765000163');

    $recordOfB = ProlaboreRecordModel::factory()->create([
        'company_id' => $companyB->id,
        'partner_id' => $partnerOfB->id,
        'user_id' => $user->id,
        'competencia' => '2026-05-01',
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->deleteJson("/companies/{$companyA->id}/prolabore-records/{$recordOfB->id}")
        ->assertNotFound();
});

// ─── Feature 006: store redireciona back (Inertia) ───────────────────────────

it('store redireciona de volta ao enviar pelo fluxo Inertia (não JSON)', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post("/companies/{$company->id}/prolabore-records", [
            'partner_id' => $partner->id,
            'competencia' => now()->format('Y-m'),
            'valor' => 5000.00,
        ]);

    $response->assertRedirect(route('dashboard'));
});

// ─── Feature 006: present() expõe origem ────────────────────────────────────

it('response JSON do store inclui o campo origem', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/prolabore-records", [
            'partner_id' => $partner->id,
            'competencia' => now()->format('Y-m'),
            'valor' => 5000.00,
        ])
        ->assertCreated()
        ->assertJsonPath('origem', 'manual');
});

// ─── Feature 006: observacao max:500 ────────────────────────────────────────

it('rejeita POST com observacao maior que 500 caracteres', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/prolabore-records", [
            'partner_id' => $partner->id,
            'competencia' => now()->format('Y-m'),
            'valor' => 3000.00,
            'observacao' => str_repeat('a', 501),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['observacao']);
});

it('rejeita PUT com observacao maior que 500 caracteres', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $currentMonth = now()->format('Y-m');
    $currentMonthDate = now()->format('Y-m').'-01';

    $record = ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => $currentMonthDate,
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/prolabore-records/{$record->id}", [
            'competencia' => $currentMonth,
            'valor' => 3500.00,
            'observacao' => str_repeat('b', 501),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['observacao']);
});
