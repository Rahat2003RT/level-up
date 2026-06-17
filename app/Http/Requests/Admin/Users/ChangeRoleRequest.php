<?php

namespace App\Http\Requests\Admin\Users;

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
                'required',
                'integer',
                Rule::in(['player', 'leader']),
            ],
        ];
    }
}
