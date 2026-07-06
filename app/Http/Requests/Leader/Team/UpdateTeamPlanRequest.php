<?php

namespace App\Http\Requests\Leader\Team;

use App\Http\Requests\ApiBaseRequest;

class UpdateTeamPlanRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'daily_calls'            => ['required', 'integer', 'min:0'],
            'daily_meetings'         => ['required', 'integer', 'min:0'],
            'business_conversations' => ['required', 'integer', 'min:0'],
            'presentations'          => ['required', 'integer', 'min:0'],
            'social_media_posts'     => ['required', 'integer', 'min:0'],
            'new_clients_per_week'   => ['required', 'integer', 'min:0'],
            'new_partners_per_week'  => ['required', 'integer', 'min:0'],
            'daily_volume_points'    => ['required', 'integer', 'min:0'],
        ];
    }
}
