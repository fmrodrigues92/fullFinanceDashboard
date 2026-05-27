<?php

declare(strict_types=1);

namespace Database\Factories\Companies;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\CompanyPartnerModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreConfigModel;

final class ProlaboreConfigModelFactory extends Factory
{
    protected $model = ProlaboreConfigModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'partner_id' => CompanyPartnerModel::factory(),
            'user_id' => User::factory(),
            'valor' => fake()->randomFloat(2, 1000, 10000),
        ];
    }
}
