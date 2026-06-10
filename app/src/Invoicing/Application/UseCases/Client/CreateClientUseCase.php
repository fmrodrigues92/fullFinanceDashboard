<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\UseCases\Client;

use Src\Invoicing\Application\DTOs\CreateClientInput;
use Src\Invoicing\Domain\Client;
use Src\Invoicing\Domain\Repositories\ClientRepository;

final readonly class CreateClientUseCase
{
    public function __construct(private ClientRepository $repository) {}

    public function __invoke(CreateClientInput $input): Client
    {
        return $this->repository->save(
            Client::create($input->companyId, $input->nome, $input->extId),
        );
    }
}
