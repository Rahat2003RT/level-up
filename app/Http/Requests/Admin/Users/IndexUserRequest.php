<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserRole;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;

final class IndexUserRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        $allowedSorts = [
            'account_id',
            'name',
            'country',
            'locale',
            'is_blocked',
            'date_register',
            'notifications_enabled',
            'deleted_at'
        ];

        return [
            'status'     => ['nullable', 'string', 'in:deleted'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'limit'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'query'      => ['nullable', 'string', 'max:100'],
            'order_by'   => ['nullable', 'string', 'in:' . implode(',', $allowedSorts)],
            'order_sort' => ['nullable', 'string', 'in:asc,desc'],
            'country'    => ['nullable', 'string', 'max:100'],
            'role' => [
                'nullable',
                'string',
                'max:32',
                // Кастомное правило для явного запрета ADMIN
                function ($attribute, $value, $fail) {
                    if ($value === \App\Enums\UserRole::ADMIN->value) {
                        $fail('The selected role is invalid.');
                    }
                },
                // Правило IN для проверки того, что роль вообще существует в системе
                Rule::in(collect(\App\Enums\UserRole::cases())->pluck('value')->toArray()),
            ],
        ];
    }
}
