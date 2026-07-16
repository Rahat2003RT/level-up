<?php

namespace App\Http\Requests\Admin\Users;

use App\Http\Requests\ApiBaseRequest;

final class BlockUserRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string'],
        ];
    }
}
