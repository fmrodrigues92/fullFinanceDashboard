<?php

declare(strict_types=1);

namespace Src\Invoicing\Application\DTOs;

final readonly class PaginatedResult
{
    public function __construct(
        public array $items,
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
    ) {}
}
