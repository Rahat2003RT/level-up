<?php

namespace App\Http\Requests\User\Profile;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'surname' => 'required|string',
            'email' => 'required|string|email',
            'phone' => 'required|string',
            'company_name' => 'required|string',
            'country' => 'required|string',
            'city' => 'required|string',
            'role' => 'nullable|string|in:' . implode(',', ['player', 'leader', 'elite']),
            'locale' => 'nullable|string|in:' . implode(',', ['en', 'fr', 'de', 'pt', 'ru', 'es']),
        ];
    }
}
