<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Application\DTOs\SyncPartnersInput;
use Src\Companies\Domain\CompanyPartner;
use Src\Companies\Domain\Exceptions\DuplicateCpfInPartnerList;
use Src\Companies\Domain\Exceptions\ParticipacaoSumNotHundred;
use Src\Companies\Domain\Repositories\CompanyRepository;
use Src\Companies\Domain\ValueObjects\Cpf;
use Src\Companies\Domain\ValueObjects\ParticipacaoPercentual;

final readonly class SyncPartnersUseCase
{
    public function __construct(private CompanyRepository $repository) {}

    public function __invoke(SyncPartnersInput $input): void
    {
        $partners = [];
        $cpfsSeen = [];

        foreach ($input->partners as $data) {
            $cpf = new Cpf($data->cpf);
            new ParticipacaoPercentual($data->participacao);

            if (in_array($cpf->value, $cpfsSeen, true)) {
                throw new DuplicateCpfInPartnerList("CPF duplicado na lista: {$cpf->value}");
            }

            $cpfsSeen[] = $cpf->value;

            $partners[] = CompanyPartner::create(
                companyId: $input->companyId,
                userId: $input->userId,
                nome: $data->nome,
                cpf: $data->cpf,
                participacao: $data->participacao,
            );
        }

        if (count($partners) > 0) {
            $sum = '0.00';
            foreach ($input->partners as $data) {
                $sum = bcadd($sum, number_format($data->participacao, 2, '.', ''), 2);
            }

            if (bccomp($sum, '100.00', 2) !== 0) {
                throw new ParticipacaoSumNotHundred(
                    "A soma das participações deve ser 100%, atual: {$sum}%",
                );
            }
        }

        $this->repository->syncPartners($input->companyId, $partners);
    }
}
