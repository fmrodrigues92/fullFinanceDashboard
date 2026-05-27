<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases;

use Src\Invoicing\Application\DTOs\UpdateClientInput;
use Src\Invoicing\Domain\Client;
use Src\Invoicing\Domain\Repositories\ClientRepository;

final readonly class UpdateClientUseCase
{
    public function __construct(private ClientRepository $repository) {}

    public function __invoke(UpdateClientInput $input): Client
    {
        return $this->repository->save(
            Client::fromPersistence($input->clientId, $input->companyId, $input->nome, $input->extId),
        );
    }
}
