<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Src\Companies\Domain\ProlaboreRecord;
use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;
use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;

final class GenerateProlaboreRecordsCommand extends Command
{
    protected $signature = 'prolabore:generate-records {--dry-run : Lista o que seria criado sem persistir}';

    protected $description = 'Gera recibos de pró-labore automáticos para o mês anterior (executa apenas no dia 1 de cada mês)';

    public function __construct(
        private readonly ProlaboreConfigRepository $configRepo,
        private readonly ProlaboreRecordRepository $recordRepo,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = now();

        if ($today->day !== 1) {
            $this->info("Hoje não é dia 1 ({$today->toDateString()}). Nenhum recibo gerado.");

            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $competencia = new DateTimeImmutable($today->copy()->subMonth()->startOfMonth()->toDateTimeString());

        $this->info("Processando competência: {$competencia->format('Y-m')}".($isDryRun ? ' [DRY-RUN]' : ''));

        $companies = CompanyModel::query()->whereNull('deleted_at')->get(['id', 'user_id']);

        $created = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            $companyId = (int) $company->id;
            $userId = (int) $company->user_id;

            // SEC-01: busca o total real do mês para calcular configs percentuais
            $faturamento = (float) InvoiceModel::query()
                ->selectRaw('COALESCE(SUM(valor_brl::numeric), 0) as total')
                ->where('company_id', $companyId)
                ->where('is_simulation', false)
                ->whereNull('deleted_at')
                ->where('data_emissao', '>=', $competencia->format('Y-m-d'))
                ->where('data_emissao', '<', $competencia->modify('+1 month')->format('Y-m-d'))
                ->value('total');

            if ($faturamento <= 0.0) {
                continue;
            }

            $configs = $this->configRepo->allForCompany($companyId);

            foreach ($configs as $config) {
                if ($this->recordRepo->existsForPartnerAndCompetencia($companyId, $config->partnerId, $competencia)) {
                    $skipped++;

                    continue;
                }

                // SEC-01: converte percentual para BRL usando o faturamento real do mês
                $valorBrl = $config->tipo === 'percentual'
                    ? round($faturamento * ($config->valor / 100.0), 2)
                    : $config->valor;

                if ($isDryRun) {
                    $this->line("  [DRY] company={$companyId} partner={$config->partnerId} valor={$valorBrl}");
                    $created++;

                    continue;
                }

                // SEC-02: captura exceção de race condition sem interromper o loop
                try {
                    DB::transaction(function () use ($companyId, $config, $userId, $competencia, $valorBrl): void {
                        $record = ProlaboreRecord::create(
                            companyId: $companyId,
                            partnerId: $config->partnerId,
                            userId: $userId,
                            competencia: $competencia,
                            valor: $valorBrl,
                            observacao: null,
                            origem: 'automatico',
                        );
                        $this->recordRepo->save($record);
                    });

                    $created++;
                } catch (\Throwable) {
                    $this->warn("Recibo já existia (race condition): company={$companyId} partner={$config->partnerId}");
                    $skipped++;
                }
            }
        }

        $this->info("Concluído: {$created} recibo(s) criado(s), {$skipped} já existente(s) ignorado(s).");

        return self::SUCCESS;
    }
}
