<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProlaboreConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
