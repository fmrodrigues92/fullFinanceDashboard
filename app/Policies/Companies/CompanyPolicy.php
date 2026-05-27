<?php

declare(strict_types=1);

namespace App\Policies\Companies;

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;

final class CompanyPolicy
{
    public function view(User $user, CompanyModel $company): bool
    {
        return (int) $company->user_id === $user->id;
    }

    public function update(User $user, CompanyModel $company): bool
    {
        return (int) $company->user_id === $user->id;
    }

    public function delete(User $user, CompanyModel $company): bool
    {
        return (int) $company->user_id === $user->id;
    }
}
