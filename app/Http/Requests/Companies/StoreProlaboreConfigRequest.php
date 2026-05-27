<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProlaboreConfigRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'partner_id' => [
                'required',
                'integer',
                Rule::unique('prolabore_configs', 'partner_id')
                    ->where('company_id', $companyId),
            ],
            'valor' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
