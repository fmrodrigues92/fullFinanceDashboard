<?php

declare(strict_types=1);

namespace App\Http\Controllers\Companies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Companies\StoreProlaboreConfigRequest;
use App\Http\Requests\Companies\UpdateProlaboreConfigRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Application\DTOs\CreateProlaboreConfigInput;
use Src\Companies\Application\DTOs\UpdateProlaboreConfigInput;
use Src\Companies\Application\UseCases\CreateProlaboreConfigUseCase;
use Src\Companies\Application\UseCases\DeleteProlaboreConfigUseCase;
use Src\Companies\Application\UseCases\ListCompanyPartnersUseCase;
use Src\Companies\Application\UseCases\ListProlaboreConfigsUseCase;
use Src\Companies\Application\UseCases\UpdateProlaboreConfigUseCase;
use Src\Companies\Domain\CompanyPartner;
use Src\Companies\Domain\ProlaboreConfig;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreConfigModel;

final class ProlaboreConfigController extends Controller
{
    public function index(
        Request $request,
        CompanyModel $company,
        ListProlaboreConfigsUseCase $listConfigs,
        ListCompanyPartnersUseCase $listPartners,
    ): InertiaResponse|JsonResponse {
        $this->authorize('view', $company);

        $configs = array_map(
            $this->present(...),
            $listConfigs((int) $company->id),
        );

        if ($request->expectsJson()) {
            return response()->json($configs);
        }

        $partners = array_map(
            $this->presentPartner(...),
            $listPartners((int) $company->id),
        );

        return Inertia::render('Companies/ProlaboreConfigs/Index', [
            'company' => ['id' => $company->id],
            'configs' => $configs,
            'partners' => $partners,
        ]);
    }

    public function store(
        StoreProlaboreConfigRequest $request,
        CompanyModel $company,
        CreateProlaboreConfigUseCase $create,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $company);

        $data = $request->validated();

        try {
            $config = $create(new CreateProlaboreConfigInput(
                companyId: (int) $company->id,
                partnerId: (int) $data['partner_id'],
                userId: (int) $request->user()->id,
                valor: (float) $data['valor'],
            ));
        } catch (\DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : redirect()->back()->withErrors(['partner_id' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->present($config), 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuração de pró-labore criada com sucesso.']);

        return redirect()->route('companies.prolabore-configs.index', $company->id);
    }

    public function update(
        UpdateProlaboreConfigRequest $request,
        CompanyModel $company,
        ProlaboreConfigModel $config,
        UpdateProlaboreConfigUseCase $update,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $config->company_id !== (int) $company->id, 404);
        $this->authorize('update', $config);

        $data = $request->validated();

        $updated = $update(new UpdateProlaboreConfigInput(
            configId: (int) $config->id,
            companyId: (int) $company->id,
            valor: (float) $data['valor'],
        ));

        if ($request->expectsJson()) {
            return response()->json($this->present($updated));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuração de pró-labore atualizada com sucesso.']);

        return redirect()->back();
    }

    public function destroy(
        Request $request,
        CompanyModel $company,
        ProlaboreConfigModel $config,
        DeleteProlaboreConfigUseCase $delete,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $config->company_id !== (int) $company->id, 404);
        $this->authorize('delete', $config);

        $delete((int) $config->id, (int) $company->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Configuração de pró-labore excluída com sucesso.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuração de pró-labore excluída com sucesso.']);

        return redirect()->route('companies.prolabore-configs.index', $company->id);
    }

    private function present(ProlaboreConfig $config): array
    {
        return [
            'id' => $config->id,
            'company_id' => $config->companyId,
            'partner_id' => $config->partnerId,
            'user_id' => $config->userId,
            'valor' => $config->valor,
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
