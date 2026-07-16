<?php

namespace App\Http\Requests\Admin\Tariff;

use App\Enums\Period;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rules\Enum;

class StoreRequest extends ApiBaseRequest
{

    public function rules(): array
    {
        return [
            'role' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'period' => ['required', new Enum(Period::class)],
            'is_active' => 'nullable|boolean',
        ];
    }
}
