<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases;

use Src\Invoicing\Application\TransactionManager;
use Src\Invoicing\Domain\Repositories\ClientRepository;

final readonly class DeleteClientUseCase
{
    public function __construct(
        private ClientRepository $repository,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(int $clientId, int $companyId): void
    {
        $client = $this->repository->findForCompany($clientId, $companyId);

        if ($client === null) {
            return;
        }

        $this->transaction->run(function () use ($client, $clientId): void {
            $this->repository->softDelete($client);
            $this->repository->nullifyClientOnInvoices($clientId);
        });
    }
}
