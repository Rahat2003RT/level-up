<?php

namespace App\Http\Requests\Admin\Notifications;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Foundation\Http\FormRequest;

final class SendMassPushRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'title'          => ['nullable', 'array'],
            'title.ru'       => ['nullable', 'string', 'max:255'],
            'title.en'       => ['nullable', 'string', 'max:255'],
            'title.es'       => ['nullable', 'string', 'max:255'],
            'title.pt'       => ['nullable', 'string', 'max:255'],
            'title.fr'       => ['nullable', 'string', 'max:255'],
            'title.de'       => ['nullable', 'string', 'max:255'],

            'description'    => ['nullable', 'array'],
            'description.ru' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.es' => ['nullable', 'string'],
            'description.pt' => ['nullable', 'string'],
            'description.fr' => ['nullable', 'string'],
            'description.de' => ['nullable', 'string'],
        ];
    }
}
