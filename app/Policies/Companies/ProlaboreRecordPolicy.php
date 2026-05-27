<?php

declare(strict_types=1);

namespace App\Policies\Companies;

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\ProlaboreRecordModel;

final class ProlaboreRecordPolicy
{
    public function update(User $user, ProlaboreRecordModel $record): bool
    {
        return (int) $record->user_id === $user->id;
    }

    public function delete(User $user, ProlaboreRecordModel $record): bool
    {
        return (int) $record->user_id === $user->id;
    }
}
