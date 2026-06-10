<?php

declare(strict_types=1);

namespace App\Policies\Invoicing;

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\ClientModel;

final class ClientPolicy
{
    public function update(User $user, ClientModel $client): bool
    {
        return CompanyModel::query()
            ->where('id', $client->company_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function delete(User $user, ClientModel $client): bool
    {
        return $this->update($user, $client);
    }
}
