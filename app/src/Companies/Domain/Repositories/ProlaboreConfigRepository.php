<?php

declare(strict_types=1);

namespace Src\Companies\Domain\Repositories;

use Src\Companies\Domain\ProlaboreConfig;

interface ProlaboreConfigRepository
{
    public function save(ProlaboreConfig $config): ProlaboreConfig;

    public function findForCompany(int $id, int $companyId): ?ProlaboreConfig;

    /** @return ProlaboreConfig[] */
    public function allForCompany(int $companyId): array;

    public function delete(ProlaboreConfig $config): void;

    /**
     * Retorna configs com nome do sócio, para o dashboard de múltiplas empresas.
     *
     * @param  int[]  $companyIds
     * @return array<int, array<int, array{valor: float, nome: string}>>
     *                                                                   [companyId][partnerId] => ['valor' => ..., 'nome' => ...]
     */
    public function configsWithPartnerNamesForCompanies(array $companyIds): array;
}
