<?php

declare(strict_types=1);

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\CompanyPartnerModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreConfigModel;

function makeCompanyWithPartner(int $userId): array
{
    $company = CompanyModel::factory()->create(['user_id' => $userId, 'cnpj' => '11222333000181']);
    $partner = CompanyPartnerModel::factory()->create([
        'company_id' => $company->id,
        'user_id' => $userId,
        'cpf' => '52998224725',
    ]);

    return [$company, $partner];
}

// ─── CA11: cria config de pró-labore ────────────────────────────────────────

it('cria config de pró-labore para um sócio e retorna id gerado', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartner($user->id);

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/prolabore-configs", [
            'partner_id' => $partner->id,
            'tipo' => 'fixo',
            'valor' => 3000.00,
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'partner_id', 'tipo', 'valor']);

    $this->assertDatabaseHas('prolabore_configs', [
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
    ]);
});

// ─── CA12: config duplicada retorna 422 ─────────────────────────────────────

it('rejeita config duplicada para o mesmo sócio e empresa', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartner($user->id);

    $this->actingAs($user)->postJson("/companies/{$company->id}/prolabore-configs", [
        'partner_id' => $partner->id,
        'tipo' => 'fixo',
        'valor' => 3000.00,
    ]);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/prolabore-configs", [
            'partner_id' => $partner->id,
            'tipo' => 'fixo',
            'valor' => 4000.00,
        ])
        ->assertUnprocessable();
});

// ─── CA13: sócio de outra empresa retorna 422 ───────────────────────────────

it('rejeita config quando sócio não pertence à empresa', function () {
    $user = User::factory()->create();
    $companyA = CompanyModel::factory()->create(['user_id' => $user->id, 'cnpj' => '11222333000181']);
    $companyB = CompanyModel::factory()->create(['user_id' => $user->id, 'cnpj' => '45539765000163']);

    $partnerOfB = CompanyPartnerModel::factory()->create([
        'company_id' => $companyB->id,
        'user_id' => $user->id,
        'cpf' => '52998224725',
    ]);

    $this->actingAs($user)
        ->postJson("/companies/{$companyA->id}/prolabore-configs", [
            'partner_id' => $partnerOfB->id,
            'tipo' => 'fixo',
            'valor' => 3000.00,
        ])
        ->assertUnprocessable();
});

// ─── CA14: exclusão do sócio remove config em cascata ───────────────────────

it('remove config em cascata ao excluir o sócio', function () {
    $user = User::factory()->create();
    [$company, $partner] = makeCompanyWithPartner($user->id);

    ProlaboreConfigModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseCount('prolabore_configs', 1);

    $partner->delete();

    $this->assertDatabaseCount('prolabore_configs', 0);
});

// ─── CA15: listagem retorna apenas configs da empresa do usuário ─────────────

it('listagem retorna configs da empresa do usuário autenticado', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    [$company, $partner] = makeCompanyWithPartner($user->id);
    $otherCompany = CompanyModel::factory()->create(['user_id' => $otherUser->id, 'cnpj' => '45539765000163']);
    $otherPartner = CompanyPartnerModel::factory()->create([
        'company_id' => $otherCompany->id,
        'user_id' => $otherUser->id,
        'cpf' => '11144477735',
    ]);

    ProlaboreConfigModel::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'user_id' => $user->id,
    ]);
    ProlaboreConfigModel::factory()->create([
        'company_id' => $otherCompany->id,
        'partner_id' => $otherPartner->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->getJson("/companies/{$company->id}/prolabore-configs")
        ->assertOk()
        ->assertJsonCount(1);
});

// ─── Bug 3: pertencimento em rotas aninhadas ─────────────────────────────────

it('retorna 404 ao tentar atualizar config de outra empresa', function () {
    $user = User::factory()->create();
    [$companyA] = makeCompanyWithPartner($user->id);
    $companyB = CompanyModel::factory()->create(['user_id' => $user->id, 'cnpj' => '45539765000163']);
    $partnerOfB = CompanyPartnerModel::factory()->create([
        'company_id' => $companyB->id,
        'user_id' => $user->id,
        'cpf' => '11144477735',
    ]);
    $configOfB = ProlaboreConfigModel::factory()->create([
        'company_id' => $companyB->id,
        'partner_id' => $partnerOfB->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$companyA->id}/prolabore-configs/{$configOfB->id}", ['tipo' => 'fixo', 'valor' => 5000.00])
        ->assertNotFound();
});

it('retorna 404 ao tentar excluir config de outra empresa', function () {
    $user = User::factory()->create();
    [$companyA] = makeCompanyWithPartner($user->id);
    $companyB = CompanyModel::factory()->create(['user_id' => $user->id, 'cnpj' => '45539765000163']);
    $partnerOfB = CompanyPartnerModel::factory()->create([
        'company_id' => $companyB->id,
        'user_id' => $user->id,
        'cpf' => '11144477735',
    ]);
    $configOfB = ProlaboreConfigModel::factory()->create([
        'company_id' => $companyB->id,
        'partner_id' => $partnerOfB->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->deleteJson("/companies/{$companyA->id}/prolabore-configs/{$configOfB->id}")
        ->assertNotFound();
});
