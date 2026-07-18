<?php

namespace App\Http\Requests\Admin\Command;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;

class AddMemberRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        $boss = $this->route('user');

        return [
            'member_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([$boss?->id]),
            ],
        ];
    }

    /**
     * Кастомные сообщения об ошибках.
     */
    public function messages(): array
    {
        return [
            'member_id.required' => 'The member ID field is required.',
            'member_id.exists'   => 'The specified user was not found in the system.',
            'member_id.not_in'   => 'A manager cannot be added to their own team.',
        ];
    }
}
