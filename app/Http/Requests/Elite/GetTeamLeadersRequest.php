<?php

namespace App\Http\Requests\Elite;

use App\Http\Requests\ApiBaseRequest;

class GetTeamLeadersRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->role?->value === 'elite' || $this->user()->role === 'elite';
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->input('page', 1),
            'per_page' => $this->input('per_page', 20),
        ]);
    }
}
