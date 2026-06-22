<?php

namespace App\Http\Requests\User\Checklist;

use App\Http\Requests\ApiBaseRequest;

class SaveChecklistRequest extends ApiBaseRequest
{
    protected function prepareForValidation(): void
    {
        $boolFields = ['social_media_activity', 'communication_with_sponsor', 'is_completed', 'is_day_off'];

        foreach ($boolFields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'scheduled_meetings'         => ['nullable', 'integer', 'min:0'],
            'completed_meetings'         => ['nullable', 'integer', 'min:0'],
            'new_clients'                => ['nullable', 'integer', 'min:0'],
            'new_partners'               => ['nullable', 'integer', 'min:0'],
            'business_conversations'     => ['nullable', 'integer', 'min:0'],
            'presentations'              => ['nullable', 'integer', 'min:0'],
            'sales'                      => ['nullable', 'integer', 'min:0'],
            'daily_income'               => ['nullable', 'integer', 'min:0'],
            'social_media_activity'      => ['nullable', 'boolean'],
            'communication_with_sponsor' => ['nullable', 'boolean'],
            'plans_for_the_day'          => ['nullable', 'string', 'max:5000'],
            'results_for_the_day'        => ['nullable', 'string', 'max:5000'],
            'notes_for_the_day'          => ['nullable', 'string', 'max:5000'],
            'is_completed'               => ['nullable', 'boolean'],
            'is_day_off'                 => ['nullable', 'boolean'],
        ];
    }
}
