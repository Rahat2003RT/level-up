<?php

namespace App\Http\Requests\Guest\Auth;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class ForgotPasswordRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }
}
