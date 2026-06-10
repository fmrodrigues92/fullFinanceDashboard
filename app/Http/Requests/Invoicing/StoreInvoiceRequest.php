<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoicing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('company'));
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')
                    ->where('company_id', $this->route('company')->id)
                    ->whereNull('deleted_at'),
            ],
            'tipo' => ['required', 'string', Rule::in(['nacional', 'internacional'])],
            'anexo_cnae' => ['required', 'integer', Rule::in([3, 5])],
            'data_emissao' => ['required', 'date_format:Y-m-d'],
            'valor_brl' => ['required', 'numeric', 'min:0.01'],
            'valor_usd' => ['nullable', 'numeric', 'min:0.01'],
            'cotacao' => ['nullable', 'numeric', 'min:0.0001'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
