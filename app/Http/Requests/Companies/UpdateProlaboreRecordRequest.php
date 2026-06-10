<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class UpdateProlaboreRecordRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = $this->route('company')?->id;
        $record = $this->route('record');

        return [
            'competencia' => [
                'required',
                'string',
                'date_format:Y-m',
                function (string $attribute, mixed $value, \Closure $fail) use ($companyId, $record): void {
                    if ($value !== date('Y-m')) {
                        $fail('Só é possível lançar o pró-labore do mês corrente por aqui.');

                        return;
                    }

                    $exists = DB::table('prolabore_records')
                        ->where('company_id', $companyId)
                        ->where('partner_id', $record?->partner_id)
                        ->where('competencia', $value.'-01')
                        ->where('id', '!=', $record?->id)
                        ->whereNull('deleted_at')
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
