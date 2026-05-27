<?php

declare(strict_types=1);

namespace Database\Factories\Companies;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\CompanyPartnerModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreRecordModel;

final class ProlaboreRecordModelFactory extends Factory
{
    protected $model = ProlaboreRecordModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'partner_id' => CompanyPartnerModel::factory(),
            'user_id' => User::factory(),
            'competencia' => '2026-05-01',
            'valor' => fake()->randomFloat(2, 1000, 10000),
            'observacao' => null,
        ];
    }
}
