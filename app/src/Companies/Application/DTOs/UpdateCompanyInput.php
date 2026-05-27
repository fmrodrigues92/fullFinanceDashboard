<?php

declare(strict_types=1);

namespace Src\Companies\Application\DTOs;

use Src\Companies\Domain\Enums\RegimeTributario;

final readonly class UpdateCompanyInput
{
    public function __construct(
        public int $companyId,
        public int $userId,
        public string $razaoSocial,
        public string $nomeFantasia,
        public string $cnpj,
        public RegimeTributario $regimeTributario,
    ) {}
}
