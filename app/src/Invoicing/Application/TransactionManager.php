<?php

declare(strict_types=1);

namespace Src\Invoicing\Application;

interface TransactionManager
{
    public function run(callable $callback): mixed;
}
