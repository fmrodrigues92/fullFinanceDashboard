<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain;

use DateTimeImmutable;
use Src\Invoicing\Domain\Enums\InvoiceTipo;
use Src\Invoicing\Domain\Exceptions\InternationalInvoiceFieldsRequired;
use Src\Invoicing\Domain\Exceptions\NationalInvoiceShouldNotHaveUsdFields;
use Src\Invoicing\Domain\ValueObjects\AnexoCnae;

final class Invoice
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $companyId,
        public readonly ?int $clientId,
        public readonly ?int $simulationBatchId,
        public readonly bool $isSimulation,
        public readonly InvoiceTipo $tipo,
        public readonly AnexoCnae $anexoCnae,
        public readonly DateTimeImmutable $dataEmissao,
        public readonly string $valorBrl,
        public readonly ?string $valorUsd,
        public readonly ?string $cotacao,
        public readonly ?string $observacao,
    ) {}

    public static function createReal(
        int $companyId,
        int $clientId,
        InvoiceTipo $tipo,
        AnexoCnae $anexoCnae,
        DateTimeImmutable $dataEmissao,
        string $valorBrl,
        ?string $valorUsd,
        ?string $cotacao,
        ?string $observacao,
    ): self {
        if ($tipo === InvoiceTipo::Internacional) {
            if ($valorUsd === null || $cotacao === null) {
                throw new InternationalInvoiceFieldsRequired('Nota internacional requer valor_usd e cotacao.');
            }
        }

        if ($tipo === InvoiceTipo::Nacional && ($valorUsd !== null || $cotacao !== null)) {
            throw new NationalInvoiceShouldNotHaveUsdFields('Nota nacional não deve ter valor_usd ou cotacao.');
        }

        return new self(
            id: null,
            companyId: $companyId,
            clientId: $clientId,
            simulationBatchId: null,
            isSimulation: false,
            tipo: $tipo,
            anexoCnae: $anexoCnae,
            dataEmissao: $dataEmissao,
            valorBrl: $valorBrl,
            valorUsd: $valorUsd,
            cotacao: $cotacao,
            observacao: $observacao,
        );
    }

    public static function createSimulation(
        int $companyId,
        int $simulationBatchId,
        InvoiceTipo $tipo,
        AnexoCnae $anexoCnae,
        DateTimeImmutable $dataEmissao,
        string $valorBrl,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            clientId: null,
            simulationBatchId: $simulationBatchId,
            isSimulation: true,
            tipo: $tipo,
            anexoCnae: $anexoCnae,
            dataEmissao: $dataEmissao,
            valorBrl: $valorBrl,
            valorUsd: null,
            cotacao: null,
            observacao: null,
        );
    }

    public static function fromPersistence(
        int $id,
        int $companyId,
        ?int $clientId,
        ?int $simulationBatchId,
        bool $isSimulation,
        InvoiceTipo $tipo,
        AnexoCnae $anexoCnae,
        DateTimeImmutable $dataEmissao,
        string $valorBrl,
        ?string $valorUsd,
        ?string $cotacao,
        ?string $observacao,
    ): self {
        return new self(
            id: $id,
            companyId: $companyId,
            clientId: $clientId,
            simulationBatchId: $simulationBatchId,
            isSimulation: $isSimulation,
            tipo: $tipo,
            anexoCnae: $anexoCnae,
            dataEmissao: $dataEmissao,
            valorBrl: $valorBrl,
            valorUsd: $valorUsd,
            cotacao: $cotacao,
            observacao: $observacao,
        );
    }
}
