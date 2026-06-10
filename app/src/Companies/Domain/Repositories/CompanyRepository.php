<?php

declare(strict_types=1);

namespace Src\Companies\Domain\Repositories;

use Src\Companies\Domain\Company;
use Src\Companies\Domain\CompanyPartner;

interface CompanyRepository
{
    public function save(Company $company): Company;

    public function findForUser(int $id, int $userId): ?Company;

    /** @return Company[] */
    public function allForUser(int $userId): array;

    public function delete(Company $company): void;

    /** @param CompanyPartner[] $partners */
    public function syncPartners(int $companyId, array $partners): void;

    /** @return CompanyPartner[] */
    public function partnersForCompany(int $companyId): array;

    public function findPartner(int $partnerId, int $companyId): ?CompanyPartner;
}
