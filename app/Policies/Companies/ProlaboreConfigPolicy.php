<?php

declare(strict_types=1);

namespace App\Policies\Companies;

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\ProlaboreConfigModel;

final class ProlaboreConfigPolicy
{
    public function update(User $user, ProlaboreConfigModel $config): bool
    {
        return (int) $config->user_id === $user->id;
    }

    public function delete(User $user, ProlaboreConfigModel $config): bool
    {
        return (int) $config->user_id === $user->id;
    }
}
