<?php

declare(strict_types=1);

namespace Src\Invoicing\Infrastructure;

use Illuminate\Support\Facades\DB;
use Src\Invoicing\Application\TransactionManager;

final readonly class DbTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
