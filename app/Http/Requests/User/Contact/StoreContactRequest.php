<?php

namespace App\Http\Requests\User\Contact;

use App\Enums\ContactType;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreContactRequest extends ApiBaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'volume' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'string', 'max:50'],
            'type' => ['required', new Enum(ContactType::class)],
            'reminder_at' => ['nullable', 'date'],
        ];
    }
}
