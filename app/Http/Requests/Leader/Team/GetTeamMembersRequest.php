<?php

namespace App\Http\Requests\Leader\Team;

use App\Http\Requests\ApiBaseRequest;

class GetTeamMembersRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->role?->value === 'leader';
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
        ];
    }
}
