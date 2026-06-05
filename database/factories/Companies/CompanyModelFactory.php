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
            'cnpj' => self::generateValidCnpj(),
            'regime_tributario' => 'simples_nacional',
        ];
    }

    private static function generateValidCnpj(): string
    {
        // 8 digits base + branch 0001
        $base = array_map(fn () => random_int(0, 9), range(1, 8));
        $branch = [0, 0, 0, 1];
        $digits = array_merge($base, $branch);

        $w1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $digits[$i] * $w1[$i];
        }
        $rem = $sum % 11;
        $digits[] = $rem < 2 ? 0 : 11 - $rem;

        $w2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $digits[$i] * $w2[$i];
        }
        $rem = $sum % 11;
        $digits[] = $rem < 2 ? 0 : 11 - $rem;

        return implode('', $digits);
    }
}
