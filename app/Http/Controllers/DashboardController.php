<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Application\UseCases\ListCompaniesUseCase;
use Src\Companies\Domain\Company;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, ListCompaniesUseCase $listCompanies): InertiaResponse|JsonResponse
    {
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
            $listCompanies((int) $request->user()->id),
        );

        return $request->expectsJson()
            ? response()->json(['companies' => $companies])
            : Inertia::render('dashboard', ['companies' => $companies]);
    }
}
