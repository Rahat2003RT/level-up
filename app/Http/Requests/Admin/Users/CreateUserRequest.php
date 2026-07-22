<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserRole;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;

final class CreateUserRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
        return [
            // Обязательные поля для создания
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'max:100'],
            'role'                  => ['required', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],

            // Дополнительные поля из редактирования профиля
            'surname'               => ['nullable', 'string', 'max:255'],
            'phone'                 => ['nullable', 'string', 'max:32'],
            'company_name'          => ['nullable', 'string', 'max:255'],
            'country'               => ['nullable', 'string', 'size:2'],
            'city'                  => ['nullable', 'string', 'max:255'],
            'locale'                => ['nullable', 'string', 'max:5', Rule::in(['ru', 'en', 'es', 'pt', 'fr', 'de'])],
            'date_of_birth'         => ['nullable', 'date', 'before:today'],
            'notifications_enabled' => ['nullable', 'boolean'],

            'goal' => ['nullable', 'array'],
            'goal.target_clients_count' => ['nullable', 'integer', 'min:0'],
            'goal.target_partners_count' => ['nullable', 'integer', 'min:0'],
            'goal.target_sales_volume' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
