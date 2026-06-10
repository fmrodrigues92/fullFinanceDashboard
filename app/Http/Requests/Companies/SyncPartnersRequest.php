<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

final class SyncPartnersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'partners' => ['present', 'array'],
            'partners.*.nome' => ['required', 'string', 'max:255'],
            'partners.*.cpf' => ['required', 'string'],
            'partners.*.participacao' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
