<?php

namespace App\Http\Requests\User\Team;

use App\Http\Requests\ApiBaseRequest;

final class AnswerInvitationRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'accept' => ['required', 'boolean'],
        ];
    }
}
