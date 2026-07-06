<?php

namespace App\Http\Resources;

use App\Models\LeadershipChecklist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeadershipChecklist
 */
class LeadershipChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id ?? null,
            'user_id'                     => $this->user_id,
            'date'                        => $this->date?->toDateString(),
            'day_number'                  => $this->day_number,
            'is_completed'                => $this->is_completed,
            'is_day_off'                  => $this->is_day_off,
            'checked_team_activity'       => $this->checked_team_activity,
            'contacted_players'           => $this->contacted_players,
            'added_new_player'            => $this->added_new_player,
            'held_online_meeting'         => $this->held_online_meeting,
            'posted_engaged_social_media' => $this->posted_engaged_social_media,
            'attracted_new_client'        => $this->attracted_new_client,
            'brought_new_partner'         => $this->brought_new_partner,
            'sent_new_invitations'        => $this->sent_new_invitations,
            'notes_for_the_day'           => $this->notes_for_the_day,
            'is_editable'                 => $this->is_editable ?? $this->isEditable(),
        ];
    }
}
