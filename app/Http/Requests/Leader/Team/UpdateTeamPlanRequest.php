<?php

namespace App\Http\Requests\Leader\Team;

use App\Http\Requests\ApiBaseRequest;

class UpdateTeamPlanRequest extends ApiBaseRequest
{
    protected function prepareForValidation(): void
    {
        $defaults = [
            'daily_calls'            => 0,
            'daily_meetings'         => 0,
            'business_conversations' => 0,
            'presentations'          => 0,
            'social_media_posts'     => 0,
            'new_clients_per_week'   => 0,
            'new_partners_per_week'  => 0,
            'daily_volume_points'    => 0,
        ];

        $this->merge(array_merge($defaults, $this->all()));
    }

    public function rules(): array
    {
        return [
            'daily_calls'            => ['sometimes', 'integer', 'min:0'],
            'daily_meetings'         => ['sometimes', 'integer', 'min:0'],
            'business_conversations' => ['sometimes', 'integer', 'min:0'],
            'presentations'          => ['sometimes', 'integer', 'min:0'],
            'social_media_posts'     => ['sometimes', 'integer', 'min:0'],
            'new_clients_per_week'   => ['sometimes', 'integer', 'min:0'],
            'new_partners_per_week'  => ['sometimes', 'integer', 'min:0'],
            'daily_volume_points'    => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
