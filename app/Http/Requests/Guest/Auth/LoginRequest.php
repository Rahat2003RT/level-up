<?php

namespace App\Http\Requests\Guest\Auth;

use App\Http\Requests\ApiBaseRequest;

final class LoginRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
