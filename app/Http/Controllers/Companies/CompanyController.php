<?php

declare(strict_types=1);

namespace App\Http\Controllers\Companies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Companies\StoreCompanyRequest;
use App\Http\Requests\Companies\UpdateCompanyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Application\DTOs\CreateCompanyInput;
use Src\Companies\Application\DTOs\UpdateCompanyInput;
use Src\Companies\Application\UseCases\CreateCompanyUseCase;
use Src\Companies\Application\UseCases\DeleteCompanyUseCase;
use Src\Companies\Application\UseCases\GetCompanyUseCase;
use Src\Companies\Application\UseCases\ListCompaniesUseCase;
use Src\Companies\Application\UseCases\ListCompanyPartnersUseCase;
use Src\Companies\Application\UseCases\UpdateCompanyUseCase;
use Src\Companies\Domain\Company;
use Src\Companies\Domain\CompanyPartner;
use Src\Companies\Domain\Enums\RegimeTributario;
use Src\Companies\Infrastructure\Persistence\CompanyModel;

final class CompanyController extends Controller
{
    public function index(Request $request, ListCompaniesUseCase $listCompanies): InertiaResponse|JsonResponse
    {
        $companies = array_map(
            $this->present(...),
            $listCompanies((int) $request->user()->id),
        );

        return $request->expectsJson()
            ? response()->json($companies)
            : Inertia::render('Companies/Index', ['companies' => $companies]);
    }

    public function store(StoreCompanyRequest $request, CreateCompanyUseCase $create): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        try {
            $company = $create(new CreateCompanyInput(
                userId: (int) $request->user()->id,
                razaoSocial: (string) $data['razao_social'],
                nomeFantasia: (string) $data['nome_fantasia'],
                cnpj: (string) $data['cnpj'],
                regimeTributario: RegimeTributario::from((string) $data['regime_tributario']),
            ));
        } catch (\DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : redirect()->back()->withErrors(['cnpj' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->present($company), 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa criada com sucesso.']);

        return redirect()->route('companies.index');
    }

    public function show(
        Request $request,
        CompanyModel $company,
        GetCompanyUseCase $getCompany,
        ListCompanyPartnersUseCase $listPartners,
    ): InertiaResponse|JsonResponse {
        $this->authorize('view', $company);

        $domainCompany = $getCompany((int) $company->id, (int) $request->user()->id);

        if ($request->expectsJson()) {
            return response()->json($this->present($domainCompany));
        }

        $partners = array_map(
            $this->presentPartner(...),
            $listPartners((int) $company->id),
        );

        return Inertia::render('Companies/Show', [
            'company' => $this->present($domainCompany),
            'partners' => $partners,
        ]);
    }

    public function update(UpdateCompanyRequest $request, CompanyModel $company, UpdateCompanyUseCase $update): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $company);

        $data = $request->validated();

        try {
            $updated = $update(new UpdateCompanyInput(
                companyId: (int) $company->id,
                userId: (int) $request->user()->id,
                razaoSocial: (string) $data['razao_social'],
                nomeFantasia: (string) $data['nome_fantasia'],
                cnpj: (string) $data['cnpj'],
                regimeTributario: RegimeTributario::from((string) $data['regime_tributario']),
            ));
        } catch (\DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : redirect()->back()->withErrors(['cnpj' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->present($updated));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa atualizada com sucesso.']);

        return redirect()->back();
    }

    public function destroy(Request $request, CompanyModel $company, DeleteCompanyUseCase $delete): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $company);

        $delete((int) $company->id, (int) $request->user()->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Empresa excluída com sucesso.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa excluída com sucesso.']);

        return redirect()->route('companies.index');
    }

    private function present(Company $company): array
    {
        return [
            'id' => $company->id,
            'razao_social' => $company->razaoSocial,
            'nome_fantasia' => $company->nomeFantasia,
            'cnpj' => $company->cnpj->value,
            'cnpj_formatted' => $company->cnpj->format(),
            'regime_tributario' => $company->regimeTributario->value,
            'regime_tributario_label' => $company->regimeTributario->label(),
        ];
    }

    private function presentPartner(CompanyPartner $partner): array
    {
        return [
            'id' => $partner->id,
            'nome' => $partner->nome,
            'cpf' => $partner->cpf->value,
            'participacao' => $partner->participacao->value,
        ];
    }
}
