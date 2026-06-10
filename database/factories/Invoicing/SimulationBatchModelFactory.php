<?php

declare(strict_types=1);

namespace Database\Factories\Invoicing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\SimulationBatchModel;

final class SimulationBatchModelFactory extends Factory
{
    protected $model = SimulationBatchModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
        ];
    }
}
