<?php

declare(strict_types=1);

namespace Database\Factories\Invoicing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\ClientModel;

final class ClientModelFactory extends Factory
{
    protected $model = ClientModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'nome' => fake()->company(),
            'ext_id' => fake()->optional()->numerify('CLI-####'),
        ];
    }
}
