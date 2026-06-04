<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoicing\StoreSimulationBatchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Application\DTOs\CreateSimulationBatchInput;
use Src\Invoicing\Application\UseCases\SimulationBatch\CreateSimulationBatchUseCase;
use Src\Invoicing\Application\UseCases\SimulationBatch\DeleteSimulationBatchUseCase;
use Src\Invoicing\Application\UseCases\SimulationBatch\ListSimulationBatchesUseCase;
use Src\Invoicing\Domain\Exceptions\SimulationConflict;
use Src\Invoicing\Domain\SimulationBatch;
use Src\Invoicing\Infrastructure\Persistence\SimulationBatchModel;

final class SimulationBatchController extends Controller
{
    public function index(
        Request $request,
        CompanyModel $company,
        ListSimulationBatchesUseCase $listBatches,
    ): InertiaResponse|JsonResponse {
        $this->authorize('view', $company);

        $batches = array_map(
            $this->present(...),
            $listBatches((int) $company->id),
        );

        return $request->expectsJson()
            ? response()->json($batches)
            : Inertia::render('Invoicing/SimulationBatches/Index', [
                'company' => ['id' => $company->id],
                'batches' => $batches,
            ]);
    }

    public function store(
        StoreSimulationBatchRequest $request,
        CompanyModel $company,
        CreateSimulationBatchUseCase $create,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $company);

        $data = $request->validated();

        try {
            $batch = $create(new CreateSimulationBatchInput(
                companyId: (int) $company->id,
                tipo: (string) $data['tipo'],
                anexoCnae: (int) $data['anexo_cnae'],
                dataInicio: (string) $data['data_inicio'],
                dataTermino: (string) $data['data_termino'],
                valorBrl: (string) $data['valor_brl'],
            ));
        } catch (SimulationConflict $e) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => $e->getMessage(),
                    'conflicting_months' => $e->conflictingMonths,
                ], 422)
                : redirect()->back()->withErrors(['data_inicio' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->present($batch), 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lote de simulações criado com sucesso.']);

        return redirect()->route('companies.simulation-batches.index', $company->id);
    }

    public function destroy(
        Request $request,
        CompanyModel $company,
        SimulationBatchModel $simulationBatch,
        DeleteSimulationBatchUseCase $delete,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $simulationBatch->company_id !== (int) $company->id, 404);
        $this->authorize('delete', $simulationBatch);

        $delete((int) $simulationBatch->id, (int) $company->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Lote de simulações excluído com sucesso.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lote de simulações excluído com sucesso.']);

        return redirect()->back();
    }

    private function present(SimulationBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'company_id' => $batch->companyId,
        ];
    }
}
