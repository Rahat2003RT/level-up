<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @mixin User
 * @property int $current_day_number
 * @property int $progress_percent
 * @property bool $is_active_today
 */
final class TeamMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name . ' ' . $this->surname,
            'avatar'             => $this->avatar,
            'current_day_number' => $this->current_day_number,
            'progress'           => $this->progress_percent,
            'status'             => $this->is_active_today,
            'plan_paused'        => $this->plan_paused,
        ];
    }
}
