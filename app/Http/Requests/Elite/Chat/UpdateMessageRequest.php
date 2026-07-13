<?php

declare(strict_types=1);

namespace App\Http\Requests\Elite\Chat;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateMessageRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:5000'],
        ];
    }
}
