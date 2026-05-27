<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoicing\StoreClientRequest;
use App\Http\Requests\Invoicing\UpdateClientRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Application\DTOs\CreateClientInput;
use Src\Invoicing\Application\DTOs\UpdateClientInput;
use Src\Invoicing\Application\UseCases\CreateClientUseCase;
use Src\Invoicing\Application\UseCases\DeleteClientUseCase;
use Src\Invoicing\Application\UseCases\ListClientsPaginatedUseCase;
use Src\Invoicing\Application\UseCases\UpdateClientUseCase;
use Src\Invoicing\Domain\Client;
use Src\Invoicing\Infrastructure\Persistence\ClientModel;

final class ClientController extends Controller
{
    private const array ALLOWED_PER_PAGE = [10, 25, 50, 100];

    public function index(
        Request $request,
        CompanyModel $company,
        ListClientsPaginatedUseCase $listClients,
    ): InertiaResponse|JsonResponse {
        $this->authorize('view', $company);

        $perPage = in_array((int) $request->query('per_page'), self::ALLOWED_PER_PAGE, true)
            ? (int) $request->query('per_page')
            : 25;
        $page = max(1, (int) ($request->query('page') ?? 1));
        $result = $listClients((int) $company->id, $page, $perPage);

        $clients = array_map($this->present(...), $result->items);
        $pagination = [
            'total' => $result->total,
            'per_page' => $result->perPage,
            'current_page' => $result->currentPage,
            'last_page' => $result->lastPage,
        ];

        return $request->expectsJson()
            ? response()->json(['data' => $clients, 'meta' => $pagination])
            : Inertia::render('Invoicing/Clients/Index', [
                'company' => ['id' => $company->id],
                'clients' => $clients,
                'pagination' => $pagination,
            ]);
    }

    public function store(
        StoreClientRequest $request,
        CompanyModel $company,
        CreateClientUseCase $create,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $company);

        $data = $request->validated();

        $client = $create(new CreateClientInput(
            companyId: (int) $company->id,
            nome: (string) $data['nome'],
            extId: isset($data['ext_id']) ? (string) $data['ext_id'] : null,
        ));

        if ($request->expectsJson()) {
            return response()->json($this->present($client), 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente criado com sucesso.']);

        return redirect()->route('companies.clients.index', $company->id);
    }

    public function update(
        UpdateClientRequest $request,
        CompanyModel $company,
        ClientModel $client,
        UpdateClientUseCase $update,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $client->company_id !== (int) $company->id, 404);
        $this->authorize('update', $client);

        $data = $request->validated();

        $updated = $update(new UpdateClientInput(
            clientId: (int) $client->id,
            companyId: (int) $company->id,
            nome: (string) $data['nome'],
            extId: isset($data['ext_id']) ? (string) $data['ext_id'] : null,
        ));

        if ($request->expectsJson()) {
            return response()->json($this->present($updated));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente atualizado com sucesso.']);

        return redirect()->back();
    }

    public function destroy(
        Request $request,
        CompanyModel $company,
        ClientModel $client,
        DeleteClientUseCase $delete,
    ): JsonResponse|RedirectResponse {
        abort_if((int) $client->company_id !== (int) $company->id, 404);
        $this->authorize('delete', $client);

        $delete((int) $client->id, (int) $company->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Cliente excluído com sucesso.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente excluído com sucesso.']);

        return redirect()->back();
    }

    private function present(Client $client): array
    {
        return [
            'id' => $client->id,
            'company_id' => $client->companyId,
            'nome' => $client->nome,
            'ext_id' => $client->extId,
        ];
    }
}
