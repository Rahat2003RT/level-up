<?php

namespace App\Http\Requests\Guest\Auth;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'exists:users,email'],
            'code'     => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
