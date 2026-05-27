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
}
