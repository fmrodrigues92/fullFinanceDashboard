<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\SimulationBatch;

use DateTimeImmutable;
use Src\Invoicing\Application\DTOs\CreateSimulationBatchInput;
use Src\Invoicing\Application\TransactionManager;
use Src\Invoicing\Domain\Enums\InvoiceTipo;
use Src\Invoicing\Domain\Exceptions\SimulationConflict;
use Src\Invoicing\Domain\Invoice;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;
use Src\Invoicing\Domain\Repositories\SimulationBatchRepository;
use Src\Invoicing\Domain\SimulationBatch;
use Src\Invoicing\Domain\ValueObjects\AnexoCnae;

final readonly class CreateSimulationBatchUseCase
{
    public function __construct(
        private SimulationBatchRepository $batchRepository,
        private InvoiceRepository $invoiceRepository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(CreateSimulationBatchInput $input): SimulationBatch
    {
        $tipo = InvoiceTipo::from($input->tipo);
        $anexo = new AnexoCnae($input->anexoCnae);
        $months = $this->generateMonths($input->dataInicio, $input->dataTermino);

        $conflicts = $this->invoiceRepository->conflictingSimulationDates(
            $input->companyId,
            $input->tipo,
            $months,
        );

        if (count($conflicts) > 0) {
            throw new SimulationConflict($conflicts);
        }

        return $this->transaction->run(function () use ($input, $tipo, $anexo, $months): SimulationBatch {
            $batch = $this->batchRepository->save(SimulationBatch::create($input->companyId));

            $simulations = array_map(
                fn (string $date) => Invoice::createSimulation(
                    companyId: $input->companyId,
                    simulationBatchId: (int) $batch->id,
                    tipo: $tipo,
                    anexoCnae: $anexo,
                    dataEmissao: new DateTimeImmutable($date),
                    valorBrl: $input->valorBrl,
                ),
                $months,
            );

            $this->invoiceRepository->insertMany($simulations);

            return $batch;
        });
    }

    /** @return string[] YYYY-MM-DD (first day of each month in range) */
    private function generateMonths(string $inicio, string $termino): array
    {
        $start = DateTimeImmutable::createFromFormat('Y-m', $inicio);
        $end = DateTimeImmutable::createFromFormat('Y-m', $termino);

        $months = [];
        $current = $start->modify('first day of this month');

        while ($current <= $end->modify('first day of this month')) {
            $months[] = $current->format('Y-m-d');
            $current = $current->modify('+1 month');
        }

        return $months;
    }
}
