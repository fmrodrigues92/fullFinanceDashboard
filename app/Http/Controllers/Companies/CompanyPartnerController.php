<?php

declare(strict_types=1);

namespace App\Http\Controllers\Companies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Companies\SyncPartnersRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Src\Companies\Application\DTOs\PartnerData;
use Src\Companies\Application\DTOs\SyncPartnersInput;
use Src\Companies\Application\UseCases\SyncPartnersUseCase;
use Src\Companies\Infrastructure\Persistence\CompanyModel;

final class CompanyPartnerController extends Controller
{
    public function update(
        SyncPartnersRequest $request,
        CompanyModel $company,
        SyncPartnersUseCase $sync,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $company);

        $data = $request->validated();

        $partners = array_map(
            fn (array $p) => new PartnerData(
                nome: (string) $p['nome'],
                cpf: (string) $p['cpf'],
                participacao: (float) $p['participacao'],
            ),
            $data['partners'],
        );

        try {
            $sync(new SyncPartnersInput(
                companyId: (int) $company->id,
                userId: (int) $request->user()->id,
                partners: $partners,
            ));
        } catch (\DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : redirect()->back()->withErrors(['partners' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Sócios sincronizados com sucesso.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sócios sincronizados com sucesso.']);

        return redirect()->back();
    }
}
