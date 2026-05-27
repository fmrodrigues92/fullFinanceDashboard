<?php

declare(strict_types=1);

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\ClientModel;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;
use Src\Invoicing\Infrastructure\Persistence\SimulationBatchModel;

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeCompanyWithClient(User $user): array
{
    $company = CompanyModel::factory()->create([
        'user_id' => $user->id,
        'cnpj' => fake()->unique()->numerify('##############'),
    ]);
    $client = ClientModel::factory()->create(['company_id' => $company->id]);

    return [$company, $client];
}

function nationalInvoicePayload(int $clientId, array $overrides = []): array
{
    return array_merge([
        'client_id' => $clientId,
        'tipo' => 'nacional',
        'anexo_cnae' => 3,
        'data_emissao' => '2026-05-01',
        'valor_brl' => '10000.00',
        'valor_usd' => null,
        'cotacao' => null,
    ], $overrides);
}

function internationalInvoicePayload(int $clientId, array $overrides = []): array
{
    return array_merge([
        'client_id' => $clientId,
        'tipo' => 'internacional',
        'anexo_cnae' => 5,
        'data_emissao' => '2026-05-01',
        'valor_brl' => '50000.00',
        'valor_usd' => '10000.00',
        'cotacao' => '5.0000',
    ], $overrides);
}

// ─── CA5: cria nota nacional ─────────────────────────────────────────────────

it('cria nota nacional com campos obrigatórios e retorna 201', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/invoices", nationalInvoicePayload($client->id));

    $response->assertCreated()
        ->assertJsonStructure(['id', 'tipo', 'valor_brl', 'data_emissao']);

    $this->assertDatabaseHas('invoices', [
        'company_id' => $company->id,
        'client_id' => $client->id,
        'tipo' => 'nacional',
        'is_simulation' => false,
    ]);
});

// ─── CA6: cria nota internacional ────────────────────────────────────────────

it('cria nota internacional com valor_usd e cotacao e retorna 201', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/invoices", internationalInvoicePayload($client->id));

    $response->assertCreated();

    $this->assertDatabaseHas('invoices', [
        'company_id' => $company->id,
        'tipo' => 'internacional',
        'valor_usd' => 10000.00,
    ]);
});

// ─── CA7: nota internacional sem valor_usd → 422 ─────────────────────────────

it('nota internacional sem valor_usd retorna 422', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/invoices", internationalInvoicePayload($client->id, [
            'valor_usd' => null,
            'cotacao' => null,
        ]))
        ->assertUnprocessable();
});

it('nota internacional sem cotacao retorna 422', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/invoices", internationalInvoicePayload($client->id, [
            'cotacao' => null,
        ]))
        ->assertUnprocessable();
});

// ─── CA8: nota real sem client_id → 422 ──────────────────────────────────────

it('nota real sem client_id retorna 422', function () {
    $user = User::factory()->create();
    $company = CompanyModel::factory()->create([
        'user_id' => $user->id,
        'cnpj' => fake()->unique()->numerify('##############'),
    ]);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/invoices", [
            'tipo' => 'nacional',
            'anexo_cnae' => 3,
            'data_emissao' => '2026-05-01',
            'valor_brl' => '1000.00',
        ])
        ->assertUnprocessable();
});

// ─── CA9: anexo_cnae fora de {3,5} → 422 ────────────────────────────────────

it('anexo_cnae fora de 3 ou 5 retorna 422', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/invoices", nationalInvoicePayload($client->id, ['anexo_cnae' => 4]))
        ->assertUnprocessable();
});

// ─── CA10: acesso a nota de empresa de outro usuário → 403 ───────────────────

it('usuário de outra empresa não lista invoices (403)', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $company = CompanyModel::factory()->create([
        'user_id' => $owner->id,
        'cnpj' => fake()->unique()->numerify('##############'),
    ]);

    $this->actingAs($intruder)
        ->getJson("/companies/{$company->id}/invoices")
        ->assertForbidden();
});

it('usuário de outra empresa não exclui invoice (403)', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $company = CompanyModel::factory()->create([
        'user_id' => $owner->id,
        'cnpj' => fake()->unique()->numerify('##############'),
    ]);
    $invoice = InvoiceModel::factory()->create(['company_id' => $company->id]);

    $this->actingAs($intruder)
        ->deleteJson("/companies/{$company->id}/invoices/{$invoice->id}")
        ->assertForbidden();
});

// ─── CA11: exclusão de nota usa soft delete ──────────────────────────────────

it('exclusão de nota real usa soft delete e não aparece na listagem', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);
    $invoice = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($user)
        ->deleteJson("/companies/{$company->id}/invoices/{$invoice->id}")
        ->assertOk();

    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);

    $response = $this->actingAs($user)
        ->getJson("/companies/{$company->id}/invoices");

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->not->toContain($invoice->id);
});

// ─── CA16: listagem retorna reais + simulações ───────────────────────────────

it('listagem sem filtro retorna reais não deletadas e simulações juntas', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $real = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'is_simulation' => false,
        'data_emissao' => '2026-01-01',
    ]);

    $batch = SimulationBatchModel::factory()
        ->create(['company_id' => $company->id]);

    $sim = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => null,
        'simulation_batch_id' => $batch->id,
        'is_simulation' => true,
        'data_emissao' => '2026-06-01',
    ]);

    $response = $this->actingAs($user)
        ->getJson("/companies/{$company->id}/invoices");

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($real->id)
        ->toContain($sim->id);
});

// ─── CA17: filtro is_simulation ──────────────────────────────────────────────

it('filtro is_simulation=true retorna apenas simulações', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $real = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'is_simulation' => false,
    ]);

    $batch = SimulationBatchModel::factory()
        ->create(['company_id' => $company->id]);

    $sim = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => null,
        'simulation_batch_id' => $batch->id,
        'is_simulation' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson("/companies/{$company->id}/invoices?is_simulation=true");

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($sim->id)
        ->not->toContain($real->id);
});

it('filtro is_simulation=false retorna apenas notas reais', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $real = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'is_simulation' => false,
    ]);

    $batch = SimulationBatchModel::factory()
        ->create(['company_id' => $company->id]);

    $sim = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => null,
        'simulation_batch_id' => $batch->id,
        'is_simulation' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson("/companies/{$company->id}/invoices?is_simulation=false");

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($real->id)
        ->not->toContain($sim->id);
});

// ─── CA18: filtro competencia=YYYY-MM ────────────────────────────────────────

it('filtro competencia filtra pelo mês de data_emissao', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $jan = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'data_emissao' => '2026-01-15',
    ]);

    $jun = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'data_emissao' => '2026-06-10',
    ]);

    $response = $this->actingAs($user)
        ->getJson("/companies/{$company->id}/invoices?competencia=2026-01");

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($jan->id)
        ->not->toContain($jun->id);
});

// ─── CA19: filtro tipo=nacional|internacional ────────────────────────────────

it('filtro tipo=nacional retorna apenas notas nacionais', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $nacional = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'tipo' => 'nacional',
    ]);

    $internacional = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'tipo' => 'internacional',
        'valor_usd' => '5000.00',
        'cotacao' => '5.0000',
    ]);

    $response = $this->actingAs($user)
        ->getJson("/companies/{$company->id}/invoices?tipo=nacional");

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($nacional->id)
        ->not->toContain($internacional->id);
});

it('filtro tipo=internacional retorna apenas notas internacionais', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);

    $nacional = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'tipo' => 'nacional',
    ]);

    $internacional = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'tipo' => 'internacional',
        'valor_usd' => '5000.00',
        'cotacao' => '5.0000',
    ]);

    $response = $this->actingAs($user)
        ->getJson("/companies/{$company->id}/invoices?tipo=internacional");

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($internacional->id)
        ->not->toContain($nacional->id);
});

// ─── Achado #1: client_id sem escopo por empresa ────────────────────────────

it('rejeita client_id inexistente com 422', function () {
    $user = User::factory()->create();
    [$company] = makeCompanyWithClient($user);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/invoices", nationalInvoicePayload(99999))
        ->assertUnprocessable();
});

it('rejeita client_id de outra empresa do mesmo usuário com 422', function () {
    $user = User::factory()->create();
    [$companyA, $clientA] = makeCompanyWithClient($user);
    [$companyB] = makeCompanyWithClient($user);

    $this->actingAs($user)
        ->postJson("/companies/{$companyB->id}/invoices", nationalInvoicePayload($clientA->id))
        ->assertUnprocessable();
});

it('rejeita client_id de empresa de outro usuário com 422', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    [, $foreignClient] = makeCompanyWithClient($other);

    $company = CompanyModel::factory()->create([
        'user_id' => $owner->id,
        'cnpj' => fake()->unique()->numerify('##############'),
    ]);

    $this->actingAs($owner)
        ->postJson("/companies/{$company->id}/invoices", nationalInvoicePayload($foreignClient->id))
        ->assertUnprocessable();
});

// ─── Achado #2: mutação individual de simulações bloqueada ──────────────────

it('PUT em simulação retorna 422', function () {
    $user = User::factory()->create();
    [$company, $client] = makeCompanyWithClient($user);
    $batch = SimulationBatchModel::factory()->create(['company_id' => $company->id]);

    $sim = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => null,
        'simulation_batch_id' => $batch->id,
        'is_simulation' => true,
    ]);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/invoices/{$sim->id}", nationalInvoicePayload($client->id))
        ->assertUnprocessable();
});

it('DELETE em simulação retorna 422', function () {
    $user = User::factory()->create();
    [$company] = makeCompanyWithClient($user);
    $batch = SimulationBatchModel::factory()->create(['company_id' => $company->id]);

    $sim = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => null,
        'simulation_batch_id' => $batch->id,
        'is_simulation' => true,
    ]);

    $this->actingAs($user)
        ->deleteJson("/companies/{$company->id}/invoices/{$sim->id}")
        ->assertUnprocessable();
});
