<?php

declare(strict_types=1);

namespace App\Policies\Invoicing;

use App\Models\User;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;

final class InvoicePolicy
{
    public function update(User $user, InvoiceModel $invoice): bool
    {
        return CompanyModel::query()
            ->where('id', $invoice->company_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function delete(User $user, InvoiceModel $invoice): bool
    {
        return $this->update($user, $invoice);
    }
}
