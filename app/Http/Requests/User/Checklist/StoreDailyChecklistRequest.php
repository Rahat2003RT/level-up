<?php

namespace App\Http\Requests\User\Checklist;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checklist = $this->route('daily_checklist');
        if ($checklist) {
            return $checklist->isEditable() && $checklist->user_id === $this->user()->id;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'scheduled_meetings'         => $this->input('scheduled_meetings') ?? 0,
            'completed_meetings'         => $this->input('completed_meetings') ?? 0,
            'new_clients'                => $this->input('new_clients') ?? 0,
            'new_partners'               => $this->input('new_partners') ?? 0,
            'business_conversations'     => $this->input('business_conversations') ?? 0,
            'presentations'              => $this->input('presentations') ?? 0,
            'sales'                      => $this->input('sales') ?? 0,
            'daily_income'               => $this->input('daily_income') ?? 0,

            'social_media_activity'      => filter_var($this->input('social_media_activity'), FILTER_VALIDATE_BOOLEAN),
            'communication_with_sponsor' => filter_var($this->input('communication_with_sponsor'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'scheduled_meetings' => 'nullable|integer|min:0',
            'completed_meetings' => 'nullable|integer|min:0',
            'new_clients' => 'nullable|integer|min:0',
            'new_partners' => 'nullable|integer|min:0',
            'business_conversations' => 'nullable|integer|min:0',
            'presentations' => 'nullable|integer|min:0',
            'sales' => 'nullable|integer|min:0',
            'daily_income' => 'nullable|integer|min:0',

            'social_media_activity' => 'nullable|boolean',
            'communication_with_sponsor' => 'nullable|boolean',

            'notes_for_the_day' => 'nullable|string|max:5000',
        ];
    }
}
