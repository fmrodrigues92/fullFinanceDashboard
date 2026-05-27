<?php

declare(strict_types=1);

namespace Database\Factories\Invoicing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\ClientModel;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;

final class InvoiceModelFactory extends Factory
{
    protected $model = InvoiceModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'client_id' => ClientModel::factory(),
            'simulation_batch_id' => null,
            'is_simulation' => false,
            'tipo' => 'nacional',
            'anexo_cnae' => fake()->randomElement([3, 5]),
            'data_emissao' => fake()->date('Y-m-d', 'now'),
            'valor_brl' => fake()->randomFloat(2, 100, 50000),
            'valor_usd' => null,
            'cotacao' => null,
            'observacao' => null,
        ];
    }

    public function internacional(): static
    {
        return $this->state(fn () => [
            'tipo' => 'internacional',
            'valor_usd' => fake()->randomFloat(2, 100, 10000),
            'cotacao' => fake()->randomFloat(4, 4.5, 6.5),
        ]);
    }

    public function simulation(int $batchId): static
    {
        return $this->state(fn () => [
            'client_id' => null,
            'simulation_batch_id' => $batchId,
            'is_simulation' => true,
        ]);
    }
}
