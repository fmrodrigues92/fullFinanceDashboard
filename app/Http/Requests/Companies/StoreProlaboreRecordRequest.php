<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class StoreProlaboreRecordRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'partner_id' => ['required', 'integer'],
            'competencia' => [
                'required',
                'string',
                'date_format:Y-m',
                function (string $attribute, mixed $value, \Closure $fail) use ($companyId): void {
                    $exists = DB::table('prolabore_records')
                        ->where('company_id', $companyId)
                        ->where('partner_id', $this->input('partner_id'))
                        ->where('competencia', $value.'-01')
                        ->exists();

                    if ($exists) {
                        $fail('Já existe um recibo para este sócio nesta competência.');
                    }
                },
            ],
            'valor' => ['required', 'numeric', 'gt:0'],
            'observacao' => ['nullable', 'string'],
        ];
    }
}
