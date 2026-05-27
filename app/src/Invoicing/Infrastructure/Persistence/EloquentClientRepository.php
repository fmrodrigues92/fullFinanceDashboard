<?php

declare(strict_types=1);

namespace Src\Invoicing\Infrastructure\Persistence;

use Src\Invoicing\Application\DTOs\PaginatedResult;
use Src\Invoicing\Domain\Client;
use Src\Invoicing\Domain\Repositories\ClientRepository;

final class EloquentClientRepository implements ClientRepository
{
    public function save(Client $client): Client
    {
        if ($client->id !== null) {
            $model = ClientModel::query()
                ->where('id', $client->id)
                ->where('company_id', $client->companyId)
                ->firstOrFail();

            $model->update([
                'nome' => $client->nome,
                'ext_id' => $client->extId,
            ]);
        } else {
            $model = ClientModel::query()->create([
                'company_id' => $client->companyId,
                'nome' => $client->nome,
                'ext_id' => $client->extId,
            ]);
        }

        return $this->toDomain($model);
    }

    public function findForCompany(int $id, int $companyId): ?Client
    {
        $model = ClientModel::query()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /** @return Client[] */
    public function allForCompany(int $companyId): array
    {
        return ClientModel::query()
            ->where('company_id', $companyId)
            ->orderBy('nome')
            ->get()
            ->map(fn (ClientModel $m) => $this->toDomain($m))
            ->all();
    }

    public function paginatedForCompany(int $companyId, int $page, int $perPage): PaginatedResult
    {
        $paginator = ClientModel::query()
            ->where('company_id', $companyId)
            ->orderBy('nome')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn (ClientModel $m) => $this->toDomain($m))
            ->all();

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    public function softDelete(Client $client): void
    {
        ClientModel::query()
            ->where('id', $client->id)
            ->where('company_id', $client->companyId)
            ->delete();
    }

    public function nullifyClientOnInvoices(int $clientId): void
    {
        InvoiceModel::query()
            ->where('client_id', $clientId)
            ->update(['client_id' => null]);
    }

    private function toDomain(ClientModel $m): Client
    {
        return Client::fromPersistence(
            id: (int) $m->id,
            companyId: (int) $m->company_id,
            nome: (string) $m->nome,
            extId: $m->ext_id !== null ? (string) $m->ext_id : null,
        );
    }
}
