<?php

declare(strict_types=1);

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;

// ─── Helpers ────────────────────────────────────────────────────────────────

function validCnpj(): string
{
    return '11222333000181';
}

function anotherValidCnpj(): string
{
    return '45539765000163';
}

function companyPayload(array $overrides = []): array
{
    return array_merge([
        'razao_social' => 'Acme Ltda',
        'nome_fantasia' => 'Acme',
        'cnpj' => validCnpj(),
        'regime_tributario' => 'simples_nacional',
    ], $overrides);
}

// ─── CA1: cria empresa com campos obrigatórios ───────────────────────────────

it('cria empresa e retorna id gerado', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/companies', companyPayload());

    $response->assertCreated()
        ->assertJsonStructure(['id', 'razao_social', 'cnpj']);

    $this->assertDatabaseHas('companies', [
        'user_id' => $user->id,
        'cnpj' => validCnpj(),
    ]);
});

// ─── CA2: CNPJ inválido retorna 422 ─────────────────────────────────────────

it('rejeita cnpj inválido com 422', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/companies', companyPayload(['cnpj' => '00000000000000']))
        ->assertUnprocessable();
});

// ─── CA3: CNPJ duplicado para o mesmo user retorna 422 ──────────────────────

it('rejeita cnpj duplicado para o mesmo usuário', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/companies', companyPayload());

    $this->actingAs($user)
        ->postJson('/companies', companyPayload(['razao_social' => 'Outra Empresa']))
        ->assertUnprocessable();
});

it('permite mesmo cnpj para usuários distintos', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA)->postJson('/companies', companyPayload())->assertCreated();
    $this->actingAs($userB)->postJson('/companies', companyPayload())->assertCreated();
});

// ─── CA4: CNPJ duplicado no update retorna 422 ──────────────────────────────

it('rejeita cnpj duplicado ao atualizar empresa', function () {
    $user = User::factory()->create();

    $companyA = CompanyModel::factory()->create(['user_id' => $user->id, 'cnpj' => validCnpj()]);
    CompanyModel::factory()->create(['user_id' => $user->id, 'cnpj' => anotherValidCnpj()]);

    $this->actingAs($user)
        ->putJson("/companies/{$companyA->id}", companyPayload(['cnpj' => anotherValidCnpj()]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cnpj']);
});

// ─── CA8: acesso à empresa de outro usuário retorna 403 ─────────────────────

it('retorna 403 ao tentar acessar empresa de outro usuário', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $company = CompanyModel::factory()->create(['user_id' => $owner->id, 'cnpj' => validCnpj()]);

    $this->actingAs($other)
        ->getJson("/companies/{$company->id}")
        ->assertForbidden();
});

it('retorna 403 ao tentar atualizar empresa de outro usuário', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $company = CompanyModel::factory()->create(['user_id' => $owner->id, 'cnpj' => validCnpj()]);

    $this->actingAs($other)
        ->putJson("/companies/{$company->id}", companyPayload(['cnpj' => anotherValidCnpj()]))
        ->assertForbidden();
});

it('retorna 403 ao tentar excluir empresa de outro usuário', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $company = CompanyModel::factory()->create(['user_id' => $owner->id, 'cnpj' => validCnpj()]);

    $this->actingAs($other)
        ->deleteJson("/companies/{$company->id}")
        ->assertForbidden();
});

// ─── CA10: listagem retorna apenas empresas do usuário ───────────────────────

it('listagem retorna apenas empresas do usuário autenticado', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    CompanyModel::factory()->create(['user_id' => $userA->id, 'razao_social' => 'Empresa A', 'cnpj' => validCnpj()]);
    CompanyModel::factory()->create(['user_id' => $userB->id, 'razao_social' => 'Empresa B', 'cnpj' => anotherValidCnpj()]);

    $response = $this->actingAs($userA)->getJson('/companies');

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.razao_social', 'Empresa A');
});
