<?php

declare(strict_types=1);

namespace Database\Factories\Companies;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Companies\Infrastructure\Persistence\CompanyModel;

final class CompanyModelFactory extends Factory
{
    protected $model = CompanyModel::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'razao_social' => fake()->company(),
            'nome_fantasia' => fake()->word(),
            'cnpj' => fake()->unique()->numerify('##############'),
            'regime_tributario' => 'simples_nacional',
        ];
    }
}
