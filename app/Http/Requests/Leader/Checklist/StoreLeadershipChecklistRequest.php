<?php

namespace App\Http\Requests\Leader\Checklist;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadershipChecklistRequest extends ApiBaseRequest
{
    public function rules(): array
    {
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
}
