<?php

declare(strict_types=1);

namespace App\Policies\Invoicing;

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\SimulationBatchModel;

final class SimulationBatchPolicy
{
    public function delete(User $user, SimulationBatchModel $batch): bool
    {
        return CompanyModel::query()
            ->where('id', $batch->company_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
