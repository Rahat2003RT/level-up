<?php

namespace App\Http\Requests\User\Profile;

use App\Http\Requests\ApiBaseRequest;

class ChangePasswordRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string', 'min:6'],
            'new_password' => ['required', 'string', 'min:6'],
        ];
    }
}
