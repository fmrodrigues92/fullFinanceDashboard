<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;

final readonly class DeleteProlaboreConfigUseCase
{
    public function __construct(private ProlaboreConfigRepository $repository) {}

    public function __invoke(int $configId, int $companyId): void
    {
        $config = $this->repository->findForCompany($configId, $companyId);

        if ($config === null) {
            return;
        }

        $this->repository->delete($config);
    }
}
