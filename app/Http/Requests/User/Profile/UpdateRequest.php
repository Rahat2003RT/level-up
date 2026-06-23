<?php

namespace App\Http\Requests\User\Profile;

use App\Enums\UserRole;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends ApiBaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('notifications_enabled')) {
            $this->merge([
                'notifications_enabled' => filter_var($this->input('notifications_enabled'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules(): array
    {
        $locales = ['ru', 'en', 'es', 'pt', 'fr', 'de'];

        return [
            'name'                  => ['nullable', 'string', 'max:255'],
            'surname'               => ['nullable', 'string', 'max:255'],
            'phone'                 => ['nullable', 'string', 'max:32'],
            'company_name'          => ['nullable', 'string', 'max:255'],
            'country'               => ['nullable', 'string', 'max:32'],
            'city'                  => ['nullable', 'string', 'max:255'],
            'locale'                => ['nullable', 'string', 'max:5', Rule::in($locales)],
            'avatar'                => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'date_of_birth'         => ['nullable', 'date', 'before:today'],
            'notifications_enabled' => ['nullable', 'boolean'],
            'device_token'          => ['nullable', 'string', 'max:255'],
        ];
    }
}
