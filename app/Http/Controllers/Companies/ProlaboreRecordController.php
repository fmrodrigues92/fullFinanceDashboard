<?php

declare(strict_types=1);

namespace App\Http\Controllers\Companies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Companies\StoreProlaboreRecordRequest;
use App\Http\Requests\Companies\UpdateProlaboreRecordRequest;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Application\DTOs\CreateProlaboreRecordInput;
use Src\Companies\Application\DTOs\UpdateProlaboreRecordInput;
use Src\Companies\Application\UseCases\CreateProlaboreRecordUseCase;
use Src\Companies\Application\UseCases\DeleteProlaboreRecordUseCase;
use Src\Companies\Application\UseCases\ListCompanyPartnersUseCase;
use Src\Companies\Application\UseCases\ListProlaboreRecordsUseCase;
use Src\Companies\Application\UseCases\UpdateProlaboreRecordUseCase;
use Src\Companies\Domain\CompanyPartner;
use Src\Companies\Domain\ProlaboreRecord;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreRecordModel;

final class ProlaboreRecordController extends Controller
{
    public function index(
        Request $request,
        CompanyModel $company,
        ListProlaboreRecordsUseCase $listRecords,
        ListCompanyPartnersUseCase $listPartners,
    ): InertiaResponse|JsonResponse {
        $this->authorize('view', $company);

        $competencia = null;
        if ($request->has('competencia')) {
            $competencia = new DateTimeImmutable($request->string('competencia')->toString().'-01');
        }

        $records = array_map(
            $this->present(...),
            $listRecords((int) $company->id, $competencia),
        );

        if ($request->expectsJson()) {
            return response()->json($records);
        }

        $partners = array_map(
            $this->presentPartner(...),
            $listPartners((int) $company->id),
        );

        return Inertia::render('Companies/ProlaboreRecords/Index', [
            'company' => ['id' => $company->id],
            'records' => $records,
            'partners' => $partners,
        ]);
    }

    public function store(
        StoreProlaboreRecordRequest $request,
        CompanyModel $company,
        CreateProlaboreRecordUseCase $create,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $company);

        $data = $request->validated();

        $competencia = new DateTimeImmutable((string) $data['competencia'].'-01');

        try {
            $record = $create(new CreateProlaboreRecordInput(
                companyId: (int) $company->id,
                partnerId: (int) $data['partner_id'],
                userId: (int) $request->user()->id,
                competencia: $competencia,
                valor: (float) $data['valor'],
                observacao: isset($data['observacao']) ? (string) $data['observacao'] : null,
            ));
        } catch (\DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : redirect()->back()->withErrors(['partner_id' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->present($record), 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recibo de pró-labore criado com sucesso.']);

        return redirect()->route('companies.prolabore-records.index', $company->id);
    }

    public function update(
        UpdateProlaboreRecordRequest $request,
        CompanyModel $company,
        ProlaboreRecordModel $record,
        UpdateProlaboreRecordUseCase $update,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $record->company_id !== (int) $company->id, 404);
        $this->authorize('update', $record);

        $data = $request->validated();

        $competencia = new DateTimeImmutable((string) $data['competencia'].'-01');

        $updated = $update(new UpdateProlaboreRecordInput(
            recordId: (int) $record->id,
            companyId: (int) $company->id,
            competencia: $competencia,
            valor: (float) $data['valor'],
            observacao: isset($data['observacao']) ? (string) $data['observacao'] : null,
        ));

        if ($request->expectsJson()) {
            return response()->json($this->present($updated));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recibo de pró-labore atualizado com sucesso.']);

        return redirect()->back();
    }

    public function destroy(
        Request $request,
        CompanyModel $company,
        ProlaboreRecordModel $record,
        DeleteProlaboreRecordUseCase $delete,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $record->company_id !== (int) $company->id, 404);
        $this->authorize('delete', $record);

        $delete((int) $record->id, (int) $company->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Recibo de pró-labore excluído com sucesso.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recibo de pró-labore excluído com sucesso.']);

        return redirect()->route('companies.prolabore-records.index', $company->id);
    }

    private function present(ProlaboreRecord $record): array
    {
        return [
            'id' => $record->id,
            'company_id' => $record->companyId,
            'partner_id' => $record->partnerId,
            'user_id' => $record->userId,
            'competencia' => $record->competencia->format('Y-m-d'),
            'valor' => $record->valor,
            'observacao' => $record->observacao,
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
