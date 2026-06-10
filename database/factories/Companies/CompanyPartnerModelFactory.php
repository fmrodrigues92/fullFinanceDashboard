<?php

declare(strict_types=1);

namespace Database\Factories\Companies;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\CompanyPartnerModel;

final class CompanyPartnerModelFactory extends Factory
{
    protected $model = CompanyPartnerModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'user_id' => User::factory(),
            'nome' => fake()->name(),
            'cpf' => fake()->unique()->numerify('###########'),
            'participacao' => '100.00',
        ];
    }
}
