<?php

namespace App\Http\Requests\User\Contact;

use App\Enums\ContactType;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateRequest extends ApiBaseRequest
{
    /**
     * Проверяем, принадлежит ли обновляемый контакт текущему пользователю.
     */
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact && $contact->user_id === $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'volume'        => ['nullable', 'string', 'max:255'],
            'comment'       => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'string', 'max:50'],
            'type'          => ['sometimes', 'nullable', new Enum(ContactType::class)],
            'reminder_at'   => ['nullable', 'date'],
        ];
    }
}
