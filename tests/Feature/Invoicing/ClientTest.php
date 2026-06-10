<?php

declare(strict_types=1);

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\ClientModel;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;

// ─── Helpers ────────────────────────────────────────────────────────────────

function clientPayload(array $overrides = []): array
{
    return array_merge([
        'nome' => 'Empresa Cliente Ltda',
        'ext_id' => 'CLI-001',
    ], $overrides);
}

function makeCompanyForUser(User $user): CompanyModel
{
    return CompanyModel::factory()->create([
        'user_id' => $user->id,
        'cnpj' => fake()->unique()->numerify('##############'),
    ]);
}

// ─── CA1: cria cliente e retorna id ─────────────────────────────────────────

it('cria cliente e retorna id gerado', function () {
    $user = User::factory()->create();
    $company = makeCompanyForUser($user);

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/clients", clientPayload());

    $response->assertCreated()
        ->assertJsonStructure(['id', 'nome', 'ext_id']);

    $this->assertDatabaseHas('clients', [
        'company_id' => $company->id,
        'nome' => 'Empresa Cliente Ltda',
    ]);
});

it('cria cliente sem ext_id', function () {
    $user = User::factory()->create();
    $company = makeCompanyForUser($user);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/clients", clientPayload(['ext_id' => null]))
        ->assertCreated();
});

// ─── CA2: listagem retorna apenas clientes da empresa ───────────────────────

it('listagem retorna apenas clientes da empresa solicitada', function () {
    $user = User::factory()->create();
    $companyA = makeCompanyForUser($user);
    $companyB = makeCompanyForUser($user);

    ClientModel::factory()->create(['company_id' => $companyA->id, 'nome' => 'Cliente A']);
    ClientModel::factory()->create(['company_id' => $companyB->id, 'nome' => 'Cliente B']);

    $response = $this->actingAs($user)
        ->getJson("/companies/{$companyA->id}/clients");

    $response->assertOk();

    $nomes = collect($response->json('data'))->pluck('nome')->all();
    expect($nomes)->toContain('Cliente A')
        ->not->toContain('Cliente B');
});

// ─── CA3: usuário de outra empresa não acessa (403) ─────────────────────────

it('usuário de outra empresa não lista clientes (403)', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $company = makeCompanyForUser($owner);

    $this->actingAs($intruder)
        ->getJson("/companies/{$company->id}/clients")
        ->assertForbidden();
});

it('usuário de outra empresa não cria cliente (403)', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $company = makeCompanyForUser($owner);

    $this->actingAs($intruder)
        ->postJson("/companies/{$company->id}/clients", clientPayload())
        ->assertForbidden();
});

it('usuário de outra empresa não atualiza cliente (403)', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $company = makeCompanyForUser($owner);
    $client = ClientModel::factory()->create(['company_id' => $company->id]);

    $this->actingAs($intruder)
        ->putJson("/companies/{$company->id}/clients/{$client->id}", clientPayload())
        ->assertForbidden();
});

// ─── CA4: exclusão soft-deleta e nulifica client_id nas notas ───────────────

it('exclusão soft-deleta o cliente', function () {
    $user = User::factory()->create();
    $company = makeCompanyForUser($user);
    $client = ClientModel::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)
        ->deleteJson("/companies/{$company->id}/clients/{$client->id}")
        ->assertOk();

    $this->assertSoftDeleted('clients', ['id' => $client->id]);
});

it('exclusão do cliente nulifica client_id nas notas vinculadas', function () {
    $user = User::factory()->create();
    $company = makeCompanyForUser($user);
    $client = ClientModel::factory()->create(['company_id' => $company->id]);

    $invoice = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($user)
        ->deleteJson("/companies/{$company->id}/clients/{$client->id}")
        ->assertOk();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'client_id' => null,
    ]);
});
