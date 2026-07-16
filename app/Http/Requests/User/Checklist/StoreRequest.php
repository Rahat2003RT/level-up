<?php

namespace App\Http\Requests\User\Checklist;

use App\Http\Requests\ApiBaseRequest;

class StoreRequest extends ApiBaseRequest
{
    protected function prepareForValidation(): void
    {
        if (!$this->user()?->can('access-leader')) {
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
    }

    public function rules(): array
    {
        if ($this->user()?->can('access-leader')) {
            return [
                'checked_team_activity'       => ['required', 'boolean'],
                'contacted_players'           => ['required', 'boolean'],
                'added_new_player'            => ['required', 'boolean'],
                'held_online_meeting'         => ['required', 'boolean'],
                'posted_engaged_social_media' => ['required', 'boolean'],
                'attracted_new_client'        => ['required', 'boolean'],
                'brought_new_partner'         => ['required', 'boolean'],
                'sent_new_invitations'        => ['required', 'boolean'],
            ];
        }

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
            'notes_for_the_day'          => ['nullable', 'string', 'max:5000'],
        ];
    }
}
