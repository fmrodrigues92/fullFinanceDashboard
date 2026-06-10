<?php

declare(strict_types=1);

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;

function makeCompany(int $userId): CompanyModel
{
    return CompanyModel::factory()->create([
        'user_id' => $userId,
        'cnpj' => '11222333000181',
    ]);
}

function partnersPayload(array $overrides = []): array
{
    return array_merge([
        'partners' => [
            ['nome' => 'João Silva', 'cpf' => '52998224725', 'participacao' => 60.00],
            ['nome' => 'Maria Souza', 'cpf' => '11144477735', 'participacao' => 40.00],
        ],
    ], $overrides);
}

// ─── CA5: sincronização com soma = 100% ─────────────────────────────────────

it('sincroniza sócios quando soma é 100%', function () {
    $user = User::factory()->create();
    $company = makeCompany($user->id);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/partners", partnersPayload())
        ->assertOk();

    $this->assertDatabaseCount('company_partners', 2);
    $this->assertDatabaseHas('company_partners', [
        'company_id' => $company->id,
        'cpf' => '52998224725',
    ]);
});

it('substitui sócios na sincronização seguinte', function () {
    $user = User::factory()->create();
    $company = makeCompany($user->id);

    $this->actingAs($user)->putJson("/companies/{$company->id}/partners", partnersPayload());

    $this->actingAs($user)->putJson("/companies/{$company->id}/partners", [
        'partners' => [
            ['nome' => 'Carlos Lima', 'cpf' => '11144477735', 'participacao' => 100.00],
        ],
    ])->assertOk();

    $this->assertDatabaseCount('company_partners', 1);
    $this->assertDatabaseHas('company_partners', ['cpf' => '11144477735']);
});

// ─── CA4: soma ≠ 100% retorna 422 com soma atual ────────────────────────────

it('rejeita sync quando soma das participações é diferente de 100%', function () {
    $user = User::factory()->create();
    $company = makeCompany($user->id);

    $response = $this->actingAs($user)
        ->putJson("/companies/{$company->id}/partners", [
            'partners' => [
                ['nome' => 'João', 'cpf' => '52998224725', 'participacao' => 60.00],
                ['nome' => 'Maria', 'cpf' => '11144477735', 'participacao' => 30.00],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('message', fn ($msg) => str_contains($msg, '90'));
});

// ─── CA6: CPF inválido retorna 422 ──────────────────────────────────────────

it('rejeita cpf inválido nos sócios', function () {
    $user = User::factory()->create();
    $company = makeCompany($user->id);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/partners", [
            'partners' => [
                ['nome' => 'Inválido', 'cpf' => '00000000000', 'participacao' => 100.00],
            ],
        ])
        ->assertUnprocessable();
});

// ─── CA7: CPF duplicado retorna 422 ─────────────────────────────────────────

it('rejeita cpf duplicado na mesma lista de sync', function () {
    $user = User::factory()->create();
    $company = makeCompany($user->id);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/partners", [
            'partners' => [
                ['nome' => 'João', 'cpf' => '52998224725', 'participacao' => 50.00],
                ['nome' => 'João Clone', 'cpf' => '52998224725', 'participacao' => 50.00],
            ],
        ])
        ->assertUnprocessable();
});

// ─── CA9: exclusão de empresa remove sócios em cascata ──────────────────────

it('exclui sócios, configs e recibos ao excluir a empresa', function () {
    $user = User::factory()->create();
    $company = makeCompany($user->id);

    $this->actingAs($user)->putJson("/companies/{$company->id}/partners", partnersPayload());

    $this->assertDatabaseCount('company_partners', 2);

    $this->actingAs($user)->deleteJson("/companies/{$company->id}")->assertOk();

    $this->assertDatabaseCount('company_partners', 0);
    $this->assertDatabaseMissing('companies', ['id' => $company->id]);
});

// ─── Empresa vazia sem sócios é válida (RN5) ────────────────────────────────

it('permite empresa sem sócios', function () {
    $user = User::factory()->create();
    $company = makeCompany($user->id);

    $this->actingAs($user)
        ->putJson("/companies/{$company->id}/partners", ['partners' => []])
        ->assertOk();
});
