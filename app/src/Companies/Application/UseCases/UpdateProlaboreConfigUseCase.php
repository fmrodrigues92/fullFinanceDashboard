<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Application\DTOs\UpdateProlaboreConfigInput;
use Src\Companies\Domain\ProlaboreConfig;
use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;

final readonly class UpdateProlaboreConfigUseCase
{
    public function __construct(private ProlaboreConfigRepository $repository) {}

    public function __invoke(UpdateProlaboreConfigInput $input): ProlaboreConfig
    {
        $config = $this->repository->findForCompany($input->configId, $input->companyId);

        if ($config === null) {
            throw new \RuntimeException('Configuração de pró-labore não encontrada.');
        }

        return $this->repository->save($config->update($input->valor));
    }
}
