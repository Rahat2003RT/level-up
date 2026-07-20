<?php

namespace App\Http\Requests\Admin\Command;

use App\Http\Requests\ApiBaseRequest;

class SearchAvailableRequest extends ApiBaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 20),
        ]);
    }

    public function rules(): array
    {
        return [
            'query'    => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ];
    }
}
