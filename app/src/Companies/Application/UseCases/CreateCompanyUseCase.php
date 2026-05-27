<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Application\DTOs\CreateCompanyInput;
use Src\Companies\Domain\Company;
use Src\Companies\Domain\Repositories\CompanyRepository;

final readonly class CreateCompanyUseCase
{
    public function __construct(private CompanyRepository $repository) {}

    public function __invoke(CreateCompanyInput $input): Company
    {
        $company = Company::create(
            userId: $input->userId,
            razaoSocial: $input->razaoSocial,
            nomeFantasia: $input->nomeFantasia,
            cnpj: $input->cnpj,
            regimeTributario: $input->regimeTributario,
        );

        return $this->repository->save($company);
    }
}
