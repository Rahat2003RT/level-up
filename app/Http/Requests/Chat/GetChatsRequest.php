<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Http\Requests\ApiBaseRequest;

final class GetChatsRequest extends ApiBaseRequest
{

    public function rules(): array
    {
        return [
            'query'   =>  ['nullable', 'string', 'max:255'],
            'page' =>     ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
