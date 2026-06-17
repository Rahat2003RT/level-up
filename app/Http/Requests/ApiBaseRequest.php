<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

    public function booleanValidated(string $key, bool $default = false): bool
    {
        return filter_var($this->validated($key, $default), FILTER_VALIDATE_BOOL);
    }

    public function integerValidated(string $key, int $default = 0): int
    {
        return (int) $this->validated($key, $default);
    }
}
