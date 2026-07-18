<?php

namespace App\Http\Requests\Admin\Tariff;

use App\Enums\Period;
use App\Enums\UserRole;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateRequest extends ApiBaseRequest
{
    protected array $locales = ['ru', 'en', 'es', 'fr', 'de', 'pt'];

    public function rules(): array
    {
        $rules = [
            'role' => [
                'nullable',
                'string',
                'max:32',
                function ($attribute, $value, $fail) {
                    if ($value === UserRole::ADMIN->value) {
                        $fail('The selected role is invalid.');
                    }
                },
                Rule::in(collect(UserRole::cases())->pluck('value')->toArray()),
            ],

            'name'        => 'sometimes|required|array|min:1',
            'description' => 'sometimes|nullable|array',

            'price'       => 'sometimes|required|numeric|min:0',
            'period'      => ['sometimes', 'required', new Enum(Period::class)],
            'is_active'   => 'sometimes|boolean',
        ];

        foreach ($this->locales as $locale) {
            $rules["name.{$locale}"] = 'sometimes|string|max:255';
            $rules["description.{$locale}"] = 'nullable|string';
        }

        return $rules;
    }
}
