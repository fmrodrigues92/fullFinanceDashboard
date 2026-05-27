<?php

declare(strict_types=1);

namespace Src\Companies\Application\UseCases;

use Src\Companies\Domain\ProlaboreConfig;
use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;

final readonly class ListProlaboreConfigsUseCase
{
    public function __construct(private ProlaboreConfigRepository $repository) {}

    /** @return ProlaboreConfig[] */
    public function __invoke(int $companyId): array
    {
        return $this->repository->allForCompany($companyId);
    }
}
