<?php

namespace App\Http\Requests\Leader\Contact;

use App\Http\Requests\ApiBaseRequest;

class UpdateLeaderContactRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'volume'        => ['nullable', 'string', 'max:255'],
            'comment'       => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'string', 'max:50'],
            'reminder_at'   => ['nullable', 'date'],
        ];
    }
}
