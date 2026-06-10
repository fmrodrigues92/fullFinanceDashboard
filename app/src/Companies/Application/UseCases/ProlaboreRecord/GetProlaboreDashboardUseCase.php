<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases\ProlaboreRecord;

use DateTimeImmutable;
use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;
use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;

final readonly class GetProlaboreDashboardUseCase
{
    public function __construct(
        private ProlaboreRecordRepository $recordRepo,
        private ProlaboreConfigRepository $configRepo,
        private InvoiceRepository $invoiceRepo,
    ) {}

    /**
     * @param  int[]  $companyIds
     * @param  array<int,string>  $regimePorEmpresa  [companyId => 'simples_nacional'|...]
     * @param  string[]  $competencias  'YYYY-MM', 13 items
     * @return array<string, array<string, array{total: float, tipo: string, fator_r: ?array, socios: list<array{nome: string, valor: float, tipo: string, partner_id: int, record_id: int|null}>}>>
     */
    public function __invoke(
        array $companyIds,
        array $regimePorEmpresa,
        array $competencias,
    ): array {
        if (empty($companyIds) || empty($competencias)) {
            return [];
        }

        $minComp = min($competencias);
        $maxComp = max($competencias);

        // Full window: 12 months before earliest competencia for Fator R history
        $fullFrom = (new DateTimeImmutable($minComp.'-01'))->modify('-12 months');
        $fullTo = (new DateTimeImmutable($maxComp.'-01'))->modify('+1 month');

        $currentMonthStart = now()->startOfMonth()->toDateTimeImmutable();

        $records = $this->recordRepo->dashboardRecordsForCompanies($companyIds, $fullFrom, $fullTo);
        $configs = $this->configRepo->configsWithPartnerNamesForCompanies($companyIds);
        $fatSummary = $this->invoiceRepo->faturamentoSummaryForCompanies($companyIds, $fullFrom, $fullTo);

        $result = [];

        foreach ($companyIds as $companyId) {
            $isSimples = ($regimePorEmpresa[$companyId] ?? '') === 'simples_nacional';
            $compConfigs = $configs[$companyId] ?? [];

            foreach ($competencias as $comp) {
                $compDate = new DateTimeImmutable($comp.'-01');
                $isPast = $compDate < $currentMonthStart;

                $compRecords = $records[$companyId][$comp] ?? [];
                $compFat = $fatSummary[$companyId][$comp] ?? ['real' => 0.0, 'simulado' => 0.0];
                $hasRealFat = $compFat['real'] > 0.0;
                $hasSimFat = $compFat['simulado'] > 0.0;

                [$tipo, $socios, $total] = $this->classify(
                    isPast: $isPast,
                    compRecords: $compRecords,
                    compConfigs: $compConfigs,
                    hasRealFat: $hasRealFat,
                    hasSimFat: $hasSimFat,
                    fatReal: $compFat['real'],
                    fatSim: $compFat['simulado'],
                );

                $fatorR = $isSimples
                    ? $this->calcFatorR(
                        compDate: $compDate,
                        isPastComp: $isPast,
                        currentMonthStart: $currentMonthStart,
                        companyRecords: $records[$companyId] ?? [],
                        companyConfigs: $compConfigs,
                        companyFat: $fatSummary[$companyId] ?? [],
                    )
                    : null;

                $result[(string) $companyId][$comp] = [
                    'total' => $total,
                    'tipo' => $tipo,
                    'fator_r' => $fatorR,
                    'socios' => $socios,
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array{id: int, valor: float, origem: string}>  $compRecords
     * @param  array<int, array{valor: float, nome: string, tipo: string}>  $compConfigs
     * @return array{0: string, 1: list<array{nome: string, valor: float, tipo: string, partner_id: int, record_id: int|null}>, 2: float}
     */
    private function classify(
        bool $isPast,
        array $compRecords,
        array $compConfigs,
        bool $hasRealFat,
        bool $hasSimFat,
        float $fatReal,
        float $fatSim,
    ): array {
        // Registros explícitos têm precedência em qualquer mês (passado, atual ou futuro)
        if (! empty($compRecords)) {
            $socios = [];
            $total = 0.0;
            $allAuto = true;

            foreach ($compRecords as $partnerId => $rec) {
                $nome = $compConfigs[$partnerId]['nome'] ?? "Sócio #{$partnerId}";
                $tipoSocio = $rec['origem'] === 'automatico' ? 'recibo_automatico' : 'recibo_manual';
                if ($tipoSocio === 'recibo_manual') {
                    $allAuto = false;
                }
                $socios[] = [
                    'nome' => $nome,
                    'valor' => $rec['valor'],
                    'tipo' => $tipoSocio,
                    'partner_id' => $partnerId,
                    'record_id' => $rec['id'],
                ];
                $total += $rec['valor'];
            }

            return [$allAuto ? 'recibo_automatico' : 'recibo_manual', $socios, $total];
        }

        if ($isPast) {
            if (! $hasRealFat) {
                return ['sem_faturamento', [], 0.0];
            }

            if (empty($compConfigs)) {
                return ['sem_config', [], 0.0];
            }

            $socios = [];
            $total = 0.0;
            foreach ($compConfigs as $partnerId => $cfg) {
                $valor = $this->configValor($cfg, $fatReal);
                $socios[] = [
                    'nome' => $cfg['nome'],
                    'valor' => $valor,
                    'tipo' => 'sem_recibo',
                    'partner_id' => $partnerId,
                    'record_id' => null,
                ];
                $total += $valor;
            }

            return ['sem_recibo', $socios, $total];
        }

        // Mês aberto ou futuro sem registros
        $hasFat = $hasRealFat || $hasSimFat;

        if (! $hasFat) {
            return ['sem_faturamento', [], 0.0];
        }

        if (empty($compConfigs)) {
            return ['sem_config', [], 0.0];
        }

        $fat = $fatReal > 0.0 ? $fatReal : $fatSim;
        $socios = [];
        $total = 0.0;
        foreach ($compConfigs as $partnerId => $cfg) {
            $valor = $this->configValor($cfg, $fat);
            $socios[] = [
                'nome' => $cfg['nome'],
                'valor' => $valor,
                'tipo' => 'previsao',
                'partner_id' => $partnerId,
                'record_id' => null,
            ];
            $total += $valor;
        }

        return ['previsao', $socios, $total];
    }

    /** Resolve o valor BRL do config para um dado faturamento (usado em configs percentuais). */
    private function configValor(array $cfg, float $fat): float
    {
        return $cfg['tipo'] === 'percentual'
            ? round($fat * ($cfg['valor'] / 100.0), 2)
            : $cfg['valor'];
    }

    /**
     * @param  array<string, array<int, array{valor: float, origem: string}>>  $companyRecords
     * @param  array<int, array{valor: float, nome: string}>  $companyConfigs
     * @param  array<string, array{real: float, simulado: float}>  $companyFat
     * @return array{percentual: float, dentro: bool, estimado: bool, rbt12: float, folha12: float}|null
     */
    private function calcFatorR(
        DateTimeImmutable $compDate,
        bool $isPastComp,
        DateTimeImmutable $currentMonthStart,
        array $companyRecords,
        array $companyConfigs,
        array $companyFat,
    ): ?array {
        $folha12 = 0.0;
        $rbt12 = 0.0;
        $estimado = ! $isPastComp;

        for ($i = 0; $i <= 11; $i++) {
            $windowDate = $compDate->modify("-{$i} months");
            $windowComp = $windowDate->format('Y-m');
            $windowIsPast = $windowDate < $currentMonthStart;

            $fat = $companyFat[$windowComp] ?? ['real' => 0.0, 'simulado' => 0.0];
            $hasRealFat = $fat['real'] > 0.0;
            $hasSimFat = $fat['simulado'] > 0.0;

            if ($hasRealFat) {
                $rbt12 += $fat['real'];
            } elseif (! $windowIsPast && $hasSimFat) {
                $rbt12 += $fat['simulado'];
                $estimado = true;
            }

            $windowRecords = $companyRecords[$windowComp] ?? [];
            if (! empty($windowRecords)) {
                foreach ($windowRecords as $rec) {
                    $folha12 += $rec['valor'];
                }
            } elseif (! empty($companyConfigs) && ($hasRealFat || (! $windowIsPast && $hasSimFat))) {
                $windowFat = $fat['real'] > 0.0 ? $fat['real'] : $fat['simulado'];
                foreach ($companyConfigs as $cfg) {
                    $folha12 += $this->configValor($cfg, $windowFat);
                }
                $estimado = true;
            }
        }

        if ($rbt12 <= 0.0) {
            return null;
        }

        $percentual = $folha12 / $rbt12;

        return [
            'percentual' => round($percentual, 4),
            'dentro' => $percentual >= 0.28,
            'estimado' => $estimado,
            'rbt12' => round($rbt12, 2),
            'folha12' => round($folha12, 2),
        ];
    }
}
