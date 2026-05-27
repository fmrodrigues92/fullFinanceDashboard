<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Companies\Domain\Enums\RegimeTributario;

final class UpdateCompanyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $this->merge(['cnpj' => preg_replace('/\D/', '', (string) $this->input('cnpj'))]);
        }
    }

    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['required', 'string', 'max:255'],
            'cnpj' => [
                'required',
                'string',
                'size:14',
                Rule::unique('companies', 'cnpj')
                    ->where('user_id', $this->user()->id)
                    ->ignore($company->id),
            ],
            'regime_tributario' => ['required', 'string', Rule::enum(RegimeTributario::class)],
        ];
    }
}
