<?php

namespace App\Http\Requests\User\Contact;

use App\Enums\ContactType;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rules\Enum;

class IndexRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', new Enum(ContactType::class)],
            'query' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

