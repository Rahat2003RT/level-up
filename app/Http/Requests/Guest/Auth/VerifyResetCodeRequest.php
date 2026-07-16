<?php

namespace App\Http\Requests\Guest\Auth;

use App\Http\Requests\ApiBaseRequest;

final class VerifyResetCodeRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }
}
