<?php

namespace App\Http\Requests\Admin\Users;

use App\Http\Requests\ApiBaseRequest;

final class IndexUserRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:deleted'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'query' => ['nullable', 'string', 'max:100'],
            'order_by' => ['nullable', 'string', 'in:id,account_id,name,created_at,country,gender,notifications_enabled,is_blocked,date_register'],
            'order_sort' => ['nullable', 'string', 'in:asc,desc'],
            'role' => 'string|in:users,captains',
            'country' => ['nullable', 'string', 'max:100'],
        ];
    }
}
