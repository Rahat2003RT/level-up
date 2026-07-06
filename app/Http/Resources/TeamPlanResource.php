<?php

namespace App\Http\Resources;

use App\Models\TeamPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TeamPlan
 */
class TeamPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'daily_calls'            => $this->daily_calls ?? 0,
            'daily_meetings'         => $this->daily_meetings ?? 0,
            'business_conversations' => $this->business_conversations ?? 0,
            'presentations'          => $this->presentations ?? 0,
            'social_media_posts'     => $this->social_media_posts ?? 0,
            'new_clients_per_week'   => $this->new_clients_per_week ?? 0,
            'new_partners_per_week'  => $this->new_partners_per_week ?? 0,
            'daily_volume_points'    => $this->daily_volume_points ?? 0,
        ];
    }
}
