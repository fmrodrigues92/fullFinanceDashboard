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

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/prolabore-records", [
            'partner_id' => $partner->id,
            'competencia' => '2026-05',
            'valor' => 3000.00,
            'observacao' => 'Pagamento maio',
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'partner_id', 'competencia', 'valor']);

    $this->assertDatabaseHas('prolabore_records', [
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'competencia' => '2026-05-01',
    ]);
});

// ─── CA17: recibo duplicado retorna 422 ─────────────────────────────────────

it('rejeita recibo duplicado para o mesmo sócio, empresa e competência', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $payload = [
        'partner_id' => $partner->id,
        'competencia' => '2026-05',
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
            'competencia' => '2026-05',
            'valor' => 3000.00,
        ])
        ->assertUnprocessable();
});

// ─── CA19: edita valor e observação ─────────────────────────────────────────

it('edita valor e observação de um recibo existente', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    $record = ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => '2026-05-01',
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/prolabore-records/{$record->id}", [
            'competencia' => '2026-05',
            'valor' => 3500.00,
            'observacao' => 'Atualizado',
        ])
        ->assertOk()
        ->assertJsonPath('valor', 3500);
});

// ─── CA19b: competência duplicada no update retorna 422 ─────────────────────

it('rejeita atualização para competência já existente do mesmo sócio', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartnerForRecord($user->id);

    ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => '2026-04-01',
        'valor' => 2000.00,
    ]);

    $recordB = ProlaboreRecordModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
        'competencia' => '2026-05-01',
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/prolabore-records/{$recordB->id}", [
            'competencia' => '2026-04',
            'valor' => 3500.00,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['competencia']);
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
        'competencia' => '2026-05-01',
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$companyA->id}/prolabore-records/{$recordOfB->id}", [
            'competencia' => '2026-05',
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
