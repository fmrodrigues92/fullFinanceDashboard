<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Application\UseCases\Company\ListCompaniesUseCase;
use Src\Companies\Application\UseCases\ProlaboreRecord\GetProlaboreDashboardUseCase;
use Src\Companies\Domain\Company;
use Src\Invoicing\Application\UseCases\Invoice\GetFaturamentoDashboardUseCase;

final class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ListCompaniesUseCase $listCompanies,
        GetFaturamentoDashboardUseCase $getFaturamento,
        GetProlaboreDashboardUseCase $getProlabore,
    ): InertiaResponse|JsonResponse {
        $userId = (int) $request->user()->id;

        $companiesDomain = $listCompanies($userId);

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
            $companiesDomain,
        );

        $competencias = $this->buildCompetencias();
        $companyIds = array_map(fn (Company $c) => $c->id, $companiesDomain);

        // Faturamento — uma query whereIn para todas as empresas
        $faturamentoPorEmpresa = $getFaturamento($companyIds, $competencias);

        // Pró-labore + Fator R — uma query por fonte (records, configs, invoices)
        $regimePorEmpresa = [];
        foreach ($companiesDomain as $company) {
            $regimePorEmpresa[$company->id] = $company->regimeTributario->value;
        }

        $prolaborePorEmpresa = $getProlabore($companyIds, $regimePorEmpresa, $competencias);

        $payload = [
            'companies' => $companies,
            'faturamentoPorEmpresa' => $faturamentoPorEmpresa,
            'prolaborePorEmpresa' => $prolaborePorEmpresa,
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
