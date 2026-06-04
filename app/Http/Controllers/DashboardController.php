<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Application\UseCases\Company\ListCompaniesUseCase;
use Src\Companies\Domain\Company;
use Src\Invoicing\Application\UseCases\Invoice\GetFaturamentoDashboardUseCase;

final class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ListCompaniesUseCase $listCompanies,
        GetFaturamentoDashboardUseCase $getFaturamento,
    ): InertiaResponse|JsonResponse {
        $userId = (int) $request->user()->id;

        $companies = array_map(
            fn (Company $company) => [
                'id' => $company->id,
                'razao_social' => $company->razaoSocial,
                'nome_fantasia' => $company->nomeFantasia,
                'cnpj' => $company->cnpj->value,
                'cnpj_formatted' => $company->cnpj->format(),
                'regime_tributario' => $company->regimeTributario->value,
                'regime_tributario_label' => $company->regimeTributario->label(),
            ],
            $listCompanies($userId),
        );

        $competencias = $this->buildCompetencias();

        $faturamentoPorEmpresa = [];
        foreach ($companies as $company) {
            $faturamentoPorEmpresa[(string) $company['id']] = $getFaturamento($company['id'], $competencias);
        }

        $payload = [
            'companies' => $companies,
            'faturamentoPorEmpresa' => $faturamentoPorEmpresa,
        ];

        return $request->expectsJson()
            ? response()->json($payload)
            : Inertia::render('dashboard', $payload);
    }

    /** @return string[] 'YYYY-MM', 13 meses centrados no mês atual */
    private function buildCompetencias(): array
    {
        $today = now();
        $months = [];
        for ($offset = -6; $offset <= 6; $offset++) {
            $months[] = $today->copy()->addMonths($offset)->format('Y-m');
        }

        return $months;
    }
}
