<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserRole;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read int $role_id ID роли: 1 - Пользователь, 3 - Капитан
 */
final class ChangeRoleRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'nullable',
                'string',
                'max:32',
                Rule::in(collect(UserRole::cases())->reject(fn($role) => $role === UserRole::ADMIN)->pluck('value')->toArray())
            ],
        ];
    }
}
