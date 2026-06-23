<?php

namespace App\Http\Requests\Guest\Auth;

use App\Enums\UserRole;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;

final class RegisterRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        $locales = ['ru', 'en', 'es', 'pt', 'fr', 'de'];
        return [
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'name'     => ['required', 'string', 'max:255'],
            'locale'                => ['nullable', 'string', 'max:5', Rule::in($locales)],
        ];
    }
}
