<?php

declare(strict_types=1);

namespace Src\Companies\Application\DTOs;

final readonly class SyncPartnersInput
{
    /**
     * @param  PartnerData[]  $partners
     */
    public function __construct(
        public int $companyId,
        public int $userId,
        public array $partners,
    ) {}
}
