<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProlaboreConfigRequest extends FormRequest
{
    public function rules(): array
    {
        $isPercentual = $this->input('tipo') === 'percentual';

        return [
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
