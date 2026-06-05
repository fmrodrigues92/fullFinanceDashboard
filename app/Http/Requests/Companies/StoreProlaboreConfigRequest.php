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

        $isPercentual = $this->input('tipo') === 'percentual';

        return [
            'partner_id' => [
                'required',
                'integer',
                Rule::unique('prolabore_configs', 'partner_id')
                    ->where('company_id', $companyId),
            ],
            'tipo' => ['required', Rule::in(['fixo', 'percentual'])],
            'valor' => array_filter([
                'required',
                'numeric',
                'gt:0',
                $isPercentual ? 'max:100' : null,
            ]),
        ];
    }
}
