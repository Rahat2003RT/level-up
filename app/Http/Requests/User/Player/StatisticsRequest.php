<?php

namespace App\Http\Requests\User\Player;

use App\Http\Requests\ApiBaseRequest;

class StatisticsRequest extends ApiBaseRequest
{

    protected function prepareForValidation(): void
    {
        $this->merge([
            'days' => $this->input('days') ?? 10,
        ]);
    }

    public function rules(): array
    {
        return [
            'days' => 'sometimes|integer|in:10,30,90'
        ];
    }
}
