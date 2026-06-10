<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoicing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSimulationBatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tipo' => ['required', 'string', Rule::in(['nacional', 'internacional'])],
            'anexo_cnae' => ['required', 'integer', Rule::in([3, 5])],
            'data_inicio' => ['required', 'date_format:Y-m'],
            'data_termino' => ['required', 'date_format:Y-m', 'gte:data_inicio'],
            'valor_brl' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
