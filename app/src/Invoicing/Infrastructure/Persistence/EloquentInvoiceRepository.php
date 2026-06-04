<?php

declare(strict_types=1);

namespace Src\Invoicing\Infrastructure\Persistence;

use DateTimeImmutable;
use Src\Invoicing\Application\DTOs\ListInvoicesFilter;
use Src\Invoicing\Application\DTOs\PaginatedResult;
use Src\Invoicing\Domain\Enums\InvoiceTipo;
use Src\Invoicing\Domain\Invoice;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;
use Src\Invoicing\Domain\ValueObjects\AnexoCnae;

final class EloquentInvoiceRepository implements InvoiceRepository
{
    public function save(Invoice $invoice): Invoice
    {
        if ($invoice->id !== null) {
            $model = InvoiceModel::query()
                ->where('id', $invoice->id)
                ->where('company_id', $invoice->companyId)
                ->firstOrFail();

            $model->update($this->toArray($invoice));
        } else {
            $model = InvoiceModel::query()->create(
                array_merge(['company_id' => $invoice->companyId], $this->toArray($invoice)),
            );
        }

        return $this->toDomain($model);
    }

    public function findForCompany(int $id, int $companyId): ?Invoice
    {
        $model = InvoiceModel::query()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function listForCompany(ListInvoicesFilter $filter): PaginatedResult
    {
        $query = InvoiceModel::query()
            ->where('company_id', $filter->companyId)
            ->where(function ($q): void {
                $q->where('is_simulation', true)
                    ->orWhereNull('deleted_at');
            })
            ->orderBy('data_emissao', 'desc');

        if ($filter->isSimulation !== null) {
            $query->where('is_simulation', $filter->isSimulation);
        }

        if ($filter->competencia !== null) {
            $start = DateTimeImmutable::createFromFormat('Y-m', $filter->competencia)
                ->modify('first day of this month');
            $end = $start->modify('first day of next month');
            $query->where('data_emissao', '>=', $start->format('Y-m-d'))
                ->where('data_emissao', '<', $end->format('Y-m-d'));
        }

        if ($filter->tipo !== null) {
            $query->where('tipo', $filter->tipo);
        }

        $paginator = $query->paginate($filter->perPage, ['*'], 'page', $filter->page);

        $items = collect($paginator->items())
            ->map(fn (InvoiceModel $m) => $this->toDomain($m))
            ->all();

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    public function softDelete(Invoice $invoice): void
    {
        InvoiceModel::query()
            ->where('id', $invoice->id)
            ->where('company_id', $invoice->companyId)
            ->delete();
    }

    /** @return string[] */
    public function conflictingSimulationDates(int $companyId, string $tipo, array $dates): array
    {
        return InvoiceModel::query()
            ->where('company_id', $companyId)
            ->where('tipo', $tipo)
            ->where('is_simulation', true)
            ->whereIn('data_emissao', $dates)
            ->pluck('data_emissao')
            ->map(fn ($d) => substr((string) $d, 0, 10))
            ->all();
    }

    public function faturamentoPorCompetencias(int $companyId, array $competencias): array
    {
        $result = [];
        foreach ($competencias as $comp) {
            $result[$comp] = ['total' => 0.0, 'notas_emitidas' => 0, 'itens' => []];
        }

        if (empty($competencias)) {
            return $result;
        }

        $minDate = min($competencias).'-01';
        $maxDate = (new DateTimeImmutable(max($competencias).'-01'))
            ->modify('first day of next month')
            ->format('Y-m-d');

        $rows = InvoiceModel::query()
            ->selectRaw("TO_CHAR(data_emissao, 'YYYY-MM') as competencia, tipo, SUM(valor_brl::numeric) as total, COUNT(*) as quantidade")
            ->where('company_id', $companyId)
            ->where('is_simulation', false)
            ->whereNull('deleted_at')
            ->where('data_emissao', '>=', $minDate)
            ->where('data_emissao', '<', $maxDate)
            ->groupByRaw("TO_CHAR(data_emissao, 'YYYY-MM'), tipo")
            ->get();

        foreach ($rows as $row) {
            $comp = $row->competencia;
            if (! isset($result[$comp])) {
                continue;
            }
            $valor = (float) $row->total;
            $qtd = (int) $row->quantidade;
            $result[$comp]['total'] += $valor;
            $result[$comp]['notas_emitidas'] += $qtd;
            $result[$comp]['itens'][] = [
                'tipo' => $row->tipo,
                'valor' => $valor,
                'quantidade' => $qtd,
            ];
        }

        return $result;
    }

    /** @param Invoice[] $invoices */
    public function insertMany(array $invoices): void
    {
        $now = now()->toDateTimeString();

        $rows = array_map(fn (Invoice $inv) => array_merge(
            ['company_id' => $inv->companyId, 'created_at' => $now, 'updated_at' => $now],
            $this->toArray($inv),
        ), $invoices);

        InvoiceModel::query()->insert($rows);
    }

    private function toArray(Invoice $invoice): array
    {
        return [
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

    private function toDomain(InvoiceModel $m): Invoice
    {
        return Invoice::fromPersistence(
            id: (int) $m->id,
            companyId: (int) $m->company_id,
            clientId: $m->client_id !== null ? (int) $m->client_id : null,
            simulationBatchId: $m->simulation_batch_id !== null ? (int) $m->simulation_batch_id : null,
            isSimulation: (bool) $m->is_simulation,
            tipo: InvoiceTipo::from((string) $m->tipo),
            anexoCnae: new AnexoCnae((int) $m->anexo_cnae),
            dataEmissao: new DateTimeImmutable((string) $m->data_emissao),
            valorBrl: (string) $m->valor_brl,
            valorUsd: $m->valor_usd !== null ? (string) $m->valor_usd : null,
            cotacao: $m->cotacao !== null ? (string) $m->cotacao : null,
            observacao: $m->observacao !== null ? (string) $m->observacao : null,
        );
    }
}
