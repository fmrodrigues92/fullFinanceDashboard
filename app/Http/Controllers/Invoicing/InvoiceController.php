<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoicing\StoreInvoiceRequest;
use App\Http\Requests\Invoicing\UpdateInvoiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Application\DTOs\CreateInvoiceInput;
use Src\Invoicing\Application\DTOs\ListInvoicesFilter;
use Src\Invoicing\Application\DTOs\UpdateInvoiceInput;
use Src\Invoicing\Application\UseCases\CreateInvoiceUseCase;
use Src\Invoicing\Application\UseCases\DeleteInvoiceUseCase;
use Src\Invoicing\Application\UseCases\ListClientsUseCase;
use Src\Invoicing\Application\UseCases\ListInvoicesUseCase;
use Src\Invoicing\Application\UseCases\UpdateInvoiceUseCase;
use Src\Invoicing\Domain\Client;
use Src\Invoicing\Domain\Exceptions\InternationalInvoiceFieldsRequired;
use Src\Invoicing\Domain\Exceptions\NationalInvoiceShouldNotHaveUsdFields;
use Src\Invoicing\Domain\Invoice;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;

final class InvoiceController extends Controller
{
    public function index(
        Request $request,
        CompanyModel $company,
        ListInvoicesUseCase $listInvoices,
        ListClientsUseCase $listClients,
    ): InertiaResponse|JsonResponse {
        $this->authorize('view', $company);

        $isSimulation = match ($request->query('is_simulation')) {
            'true' => true,
            'false' => false,
            default => null,
        };

        $allowedPerPage = [10, 25, 50, 100];
        $perPage = in_array((int) $request->query('per_page'), $allowedPerPage, true)
            ? (int) $request->query('per_page')
            : 25;

        $result = $listInvoices(new ListInvoicesFilter(
            companyId: (int) $company->id,
            isSimulation: $isSimulation,
            competencia: $request->query('competencia') ?: null,
            tipo: $request->query('tipo') ?: null,
            page: max(1, (int) ($request->query('page') ?? 1)),
            perPage: $perPage,
        ));

        $invoices = array_map($this->present(...), $result->items);

        $pagination = [
            'total' => $result->total,
            'per_page' => $result->perPage,
            'current_page' => $result->currentPage,
            'last_page' => $result->lastPage,
        ];

        if ($request->expectsJson()) {
            return response()->json(['data' => $invoices, 'meta' => $pagination]);
        }

        $clients = array_map(
            fn (Client $c) => ['id' => $c->id, 'nome' => $c->nome],
            $listClients((int) $company->id),
        );

        return Inertia::render('Invoicing/Invoices/Index', [
            'company' => ['id' => $company->id],
            'invoices' => $invoices,
            'pagination' => $pagination,
            'clients' => $clients,
            'filters' => [
                'is_simulation' => $request->query('is_simulation') ?? '',
                'competencia' => $request->query('competencia') ?? '',
                'tipo' => $request->query('tipo') ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(
        StoreInvoiceRequest $request,
        CompanyModel $company,
        CreateInvoiceUseCase $create,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $company);

        $data = $request->validated();

        try {
            $invoice = $create(new CreateInvoiceInput(
                companyId: (int) $company->id,
                clientId: (int) $data['client_id'],
                tipo: (string) $data['tipo'],
                anexoCnae: (int) $data['anexo_cnae'],
                dataEmissao: (string) $data['data_emissao'],
                valorBrl: (string) $data['valor_brl'],
                valorUsd: isset($data['valor_usd']) ? (string) $data['valor_usd'] : null,
                cotacao: isset($data['cotacao']) ? (string) $data['cotacao'] : null,
                observacao: isset($data['observacao']) ? (string) $data['observacao'] : null,
            ));
        } catch (InternationalInvoiceFieldsRequired|NationalInvoiceShouldNotHaveUsdFields $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : redirect()->back()->withErrors(['tipo' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->present($invoice), 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nota fiscal criada com sucesso.']);

        return redirect()->route('companies.invoices.index', $company->id);
    }

    public function update(
        UpdateInvoiceRequest $request,
        CompanyModel $company,
        InvoiceModel $invoice,
        UpdateInvoiceUseCase $update,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $invoice->company_id !== (int) $company->id, 404);
        abort_if((bool) $invoice->is_simulation, 422, 'Simulações não podem ser alteradas individualmente.');
        $this->authorize('update', $invoice);

        $data = $request->validated();

        try {
            $updated = $update(new UpdateInvoiceInput(
                invoiceId: (int) $invoice->id,
                companyId: (int) $company->id,
                clientId: (int) $data['client_id'],
                tipo: (string) $data['tipo'],
                anexoCnae: (int) $data['anexo_cnae'],
                dataEmissao: (string) $data['data_emissao'],
                valorBrl: (string) $data['valor_brl'],
                valorUsd: isset($data['valor_usd']) ? (string) $data['valor_usd'] : null,
                cotacao: isset($data['cotacao']) ? (string) $data['cotacao'] : null,
                observacao: isset($data['observacao']) ? (string) $data['observacao'] : null,
            ));
        } catch (InternationalInvoiceFieldsRequired|NationalInvoiceShouldNotHaveUsdFields $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : redirect()->back()->withErrors(['tipo' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->present($updated));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nota fiscal atualizada com sucesso.']);

        return redirect()->back();
    }

    public function destroy(
        Request $request,
        CompanyModel $company,
        InvoiceModel $invoice,
        DeleteInvoiceUseCase $delete,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $invoice->company_id !== (int) $company->id, 404);
        abort_if((bool) $invoice->is_simulation, 422, 'Simulações não podem ser excluídas individualmente.');
        $this->authorize('delete', $invoice);

        $delete((int) $invoice->id, (int) $company->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Nota fiscal excluída com sucesso.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nota fiscal excluída com sucesso.']);

        return redirect()->back();
    }

    private function present(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'company_id' => $invoice->companyId,
            'client_id' => $invoice->clientId,
            'simulation_batch_id' => $invoice->simulationBatchId,
            'is_simulation' => $invoice->isSimulation,
            'tipo' => $invoice->tipo->value,
            'anexo_cnae' => $invoice->anexoCnae->value,
            'data_emissao' => $invoice->dataEmissao->format('Y-m-d'),
            'valor_brl' => $invoice->valorBrl,
            'valor_usd' => $invoice->valorUsd,
            'cotacao' => $invoice->cotacao,
            'observacao' => $invoice->observacao,
        ];
    }
}
