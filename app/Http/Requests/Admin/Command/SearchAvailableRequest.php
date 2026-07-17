<?php

namespace App\Http\Requests\Admin\Command;

use App\Http\Requests\ApiBaseRequest;

class SearchAvailableRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        return array_merge([
            'per_page' => 20,
        ], parent::validated());
    }
}
