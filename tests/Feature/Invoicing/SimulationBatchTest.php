<?php

declare(strict_types=1);

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\ClientModel;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;
use Src\Invoicing\Infrastructure\Persistence\SimulationBatchModel;

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeInvoicingCompany(User $user): CompanyModel
{
    return CompanyModel::factory()->create([
        'user_id' => $user->id,
        'cnpj' => fake()->unique()->numerify('##############'),
    ]);
}

function batchPayload(array $overrides = []): array
{
    return array_merge([
        'tipo' => 'nacional',
        'anexo_cnae' => 3,
        'data_inicio' => '2026-01',
        'data_termino' => '2026-03',
        'valor_brl' => '15000.00',
    ], $overrides);
}

// ─── CA12: POST do lote cria uma simulação por mês no intervalo ──────────────

it('POST do lote cria uma simulação por mês no intervalo', function () {
    $user = User::factory()->create();
    $company = makeInvoicingCompany($user);

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/simulation-batches", batchPayload([
            'data_inicio' => '2026-01',
            'data_termino' => '2026-03',
        ]));

    $response->assertCreated()->assertJsonStructure(['id', 'company_id']);

    $batchId = $response->json('id');

    $this->assertDatabaseCount('invoices', 3);

    $this->assertDatabaseHas('invoices', ['simulation_batch_id' => $batchId, 'data_emissao' => '2026-01-01']);
    $this->assertDatabaseHas('invoices', ['simulation_batch_id' => $batchId, 'data_emissao' => '2026-02-01']);
    $this->assertDatabaseHas('invoices', ['simulation_batch_id' => $batchId, 'data_emissao' => '2026-03-01']);
});

it('lote de mês único cria exatamente uma simulação', function () {
    $user = User::factory()->create();
    $company = makeInvoicingCompany($user);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/simulation-batches", batchPayload([
            'data_inicio' => '2026-06',
            'data_termino' => '2026-06',
        ]))
        ->assertCreated();

    $this->assertDatabaseCount('invoices', 1);
    $this->assertDatabaseHas('invoices', ['data_emissao' => '2026-06-01', 'is_simulation' => true]);
});

// ─── CA13: lote com mês conflitante → 422 com meses listados ────────────────

it('lote com mês já ocupado retorna 422 indicando os conflitos', function () {
    $user = User::factory()->create();
    $company = makeInvoicingCompany($user);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/simulation-batches", batchPayload([
            'data_inicio' => '2026-01',
            'data_termino' => '2026-02',
        ]))
        ->assertCreated();

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/simulation-batches", batchPayload([
            'data_inicio' => '2026-02',
            'data_termino' => '2026-04',
        ]));

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'conflicting_months']);

    expect($response->json('conflicting_months'))->toContain('2026-02-01');
});

it('lote de tipo diferente no mesmo mês não conflita', function () {
    $user = User::factory()->create();
    $company = makeInvoicingCompany($user);

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/simulation-batches", batchPayload([
            'tipo' => 'nacional',
            'data_inicio' => '2026-05',
            'data_termino' => '2026-05',
        ]))
        ->assertCreated();

    $this->actingAs($user)
        ->postJson("/companies/{$company->id}/simulation-batches", batchPayload([
            'tipo' => 'internacional',
            'data_inicio' => '2026-05',
            'data_termino' => '2026-05',
        ]))
        ->assertCreated();

    $this->assertDatabaseCount('invoices', 2);
});

// ─── CA14: DELETE do lote remove batch e hard-deleta simulações ──────────────

it('DELETE do lote remove o batch e hard-deleta todas as simulações', function () {
    $user = User::factory()->create();
    $company = makeInvoicingCompany($user);

    $response = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/simulation-batches", batchPayload([
            'data_inicio' => '2026-01',
            'data_termino' => '2026-02',
        ]))
        ->assertCreated();

    $batchId = $response->json('id');

    $this->actingAs($user)
        ->deleteJson("/companies/{$company->id}/simulation-batches/{$batchId}")
        ->assertOk();

    $this->assertDatabaseMissing('simulation_batches', ['id' => $batchId]);
    $this->assertDatabaseCount('invoices', 0);
});

it('usuário de outra empresa não exclui lote (403)', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $company = makeInvoicingCompany($owner);
    $batch = SimulationBatchModel::factory()->create(['company_id' => $company->id]);

    $this->actingAs($intruder)
        ->deleteJson("/companies/{$company->id}/simulation-batches/{$batch->id}")
        ->assertForbidden();
});

// ─── CA15: GET /invoices retorna simulações após criação do lote ─────────────

it('após criar o lote GET invoices retorna simulações junto com notas reais', function () {
    $user = User::factory()->create();
    $company = makeInvoicingCompany($user);
    $client = ClientModel::factory()->create(['company_id' => $company->id]);

    $real = InvoiceModel::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'is_simulation' => false,
        'data_emissao' => '2026-04-15',
    ]);

    $batchResponse = $this->actingAs($user)
        ->postJson("/companies/{$company->id}/simulation-batches", batchPayload([
            'data_inicio' => '2026-06',
            'data_termino' => '2026-06',
        ]))
        ->assertCreated();

    $batchId = $batchResponse->json('id');

    $listResponse = $this->actingAs($user)
        ->getJson("/companies/{$company->id}/invoices");

    $listResponse->assertOk();

    $isSimValues = collect($listResponse->json('data'))->pluck('is_simulation')->all();
    expect($isSimValues)->toContain(true)->toContain(false);

    $simIds = collect($listResponse->json('data'))
        ->where('simulation_batch_id', $batchId)
        ->pluck('id')
        ->all();

    expect($simIds)->not->toBeEmpty();
});
