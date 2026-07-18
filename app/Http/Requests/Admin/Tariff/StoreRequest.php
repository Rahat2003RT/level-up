<?php

namespace App\Http\Requests\Admin\Tariff;

use App\Enums\Period;
use App\Enums\UserRole;
use App\Http\Requests\ApiBaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreRequest extends ApiBaseRequest
{
    /**
     * Список поддерживаемых языков.
     */
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

            'name'        => 'required|array|min:1',
            'description' => 'nullable|array',
            'price'       => 'required|numeric|min:0',
            'period'      => ['required', new Enum(Period::class)],
            'is_active'   => 'nullable|boolean',
        ];
        foreach ($this->locales as $locale) {
            $rules["name.$locale"] = 'required_without_all_others|string|max:255';
            $rules["description.$locale"] = 'nullable|string';
        }

        return $rules;
    }
}
