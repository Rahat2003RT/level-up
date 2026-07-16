<?php

namespace App\Http\Requests\Admin\Tariff;

use App\Enums\Period;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'role' => 'nullable|string|max:255',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'period' => ['sometimes', 'required', new Enum(Period::class)],
            'is_active' => 'sometimes|boolean',
        ];
    }
}
