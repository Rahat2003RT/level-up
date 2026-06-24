<?php

namespace App\Http\Requests\Admin\Auth;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * @property string $nickname
 * @property string $password
 */
final class LoginRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
