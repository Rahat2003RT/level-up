<?php

declare(strict_types=1);

namespace App\Http\Requests\Elite\Chat;

use App\Http\Requests\ApiBaseRequest;

final class GetChatsRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'], // поиск по имени лидера
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
