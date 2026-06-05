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
            'partner_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($companyId): void {
                    $belongs = DB::table('company_partners')
                        ->where('company_id', $companyId)
                        ->where('id', $value)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $belongs) {
                        $fail('O sócio informado não pertence a esta empresa.');
                    }
                },
            ],
            'competencia' => [
                'required',
                'string',
                'date_format:Y-m',
                function (string $attribute, mixed $value, \Closure $fail) use ($companyId): void {
                    if ($value !== date('Y-m')) {
                        $fail('Só é possível lançar o pró-labore do mês corrente por aqui.');

                        return;
                    }

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
            'observacao' => ['nullable', 'string', 'max:500'],
        ];
    }
}
