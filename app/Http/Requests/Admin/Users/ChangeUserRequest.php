<?php

namespace App\Http\Requests\Admin\Users;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;

/**
 * @property mixed $notifications_enabled
 */
final class ChangeUserRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        $userId = $this->route('user');
        return [
            'account_id'                 => [
                'nullable',
                'string',
                'alpha_num',
                'max:255',
                Rule::unique('users', 'account_id')->ignore($userId),
            ],
            'first_name'                 => 'nullable|string',
            'last_name'                  => 'nullable|string',
            'phone'                      => 'nullable|string',
            'avatar'                     => 'nullable|image|max:4096',
            'date_of_birth'              => 'nullable|date',
            'age'                        => 'nullable|integer',
            'gender'                     => 'nullable|string',
            'locale'                     => 'nullable|string',
            'country'                    => 'nullable|string',
            'notifications_enabled'      => 'nullable|boolean',
            'timezone'                   => ['nullable', 'string', 'timezone'],
            'goal'                       => ['nullable', 'array'],
            'goal.target_clients_count'  => ['nullable', 'integer', 'min:0'],
            'goal.target_partners_count' => ['nullable', 'integer', 'min:0'],
            'goal.target_sales_volume'   => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notifications_enabled' => filter_var($this->notifications_enabled, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
