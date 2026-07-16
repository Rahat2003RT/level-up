<?php

namespace App\Http\Requests\User\Statistics;

use App\Http\Requests\ApiBaseRequest;

class IndexRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'days' => $this->input('days') ?? 10,
        ]);
    }

    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', 'in:10,30,90'],
        ];
    }
}
