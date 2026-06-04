<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases\Company;

use Src\Companies\Application\DTOs\UpdateCompanyInput;
use Src\Companies\Domain\Company;
use Src\Companies\Domain\Repositories\CompanyRepository;

final readonly class UpdateCompanyUseCase
{
    public function __construct(private CompanyRepository $repository) {}

    public function __invoke(UpdateCompanyInput $input): Company
    {
        $company = $this->repository->findForUser($input->companyId, $input->userId);

        if ($company === null) {
            throw new \RuntimeException('Empresa não encontrada.');
        }

        $updated = $company->update(
            razaoSocial: $input->razaoSocial,
            nomeFantasia: $input->nomeFantasia,
            cnpj: $input->cnpj,
            regimeTributario: $input->regimeTributario,
        );

        return $this->repository->save($updated);
    }
}
