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
