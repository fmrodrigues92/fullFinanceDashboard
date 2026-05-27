<?php

declare(strict_types=1);

namespace Src\Invoicing\Domain\Exceptions;

use DomainException;

final class SimulationConflict extends DomainException
{
    /** @param string[] $conflictingMonths YYYY-MM-DD */
    public function __construct(
        public readonly array $conflictingMonths,
        string $message = 'Conflito de simulações para os meses informados.',
    ) {
        parent::__construct($message);
    }
}
