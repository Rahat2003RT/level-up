<?php

namespace App\Http\Requests\User\Player;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StatisticsRequest extends ApiBaseRequest
{

    protected function prepareForValidation(): void
    {
        $this->merge([
            'days' => $this->input('days') ?? 10,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'days' => 'sometimes|integer|in:10,30,90'
        ];
    }
}
