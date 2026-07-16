<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Models\UserGoal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Class UserResource
 * @package App\Http\Resources
 * @mixin User
 * @property string|null $token
 * @property-read UserGoal|null $goal
 */
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isLeader = $this->role?->value === 'leader';
        $todayStr = Carbon::today()->toDateString();
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'leader_id' => $this->leader_id,
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'phone' => $this->phone,

            'avatar' => match (true) {
                empty($this->avatar_path) => null,
                filter_var($this->avatar_path, FILTER_VALIDATE_URL) => $this->avatar_path,
                default => Storage::disk('public')->url($this->avatar_path),
            },

            'country' => $this->country,
            'city' => $this->city,
            'company_name' => $this->company_name,
            'timezone' => $this->timezone,
            'date_of_birth' => $this->date_of_birth,
            'notifications_enabled' => $this->notifications_enabled,
            'locale' => $this->locale,

            'role' => $this->role?->value,
            'plan' => $this->plan?->value,
            'is_onboarded' => $this->is_onboarded,

            'is_blocked' => !is_null($this->blocked_at),
            'is_deleted' => $this->trashed(),

            $this->mergeWhen(!is_null($this->blocked_at), [
                'blocked_at' => $this->blocked_at?->toIso8601String(),
                'block_reason' => $this->block_reason,
            ]),

            $this->mergeWhen($this->trashed(), [
                'deleted_at' => $this->deleted_at?->toIso8601String(),
            ]),

            'token' => $this->when(!empty($this->token), $this->token),

            'device_tokens' => $this->whenLoaded('deviceTokens', function () {
                return $this->deviceTokens->pluck('token');
            }),

            'goal' => UserGoalResource::make($this->whenLoaded('goal')),

            'team' => $this->whenLoaded('leader', function () {
                if (!$this->leader) {
                    return null;
                }

                return [
                    'id' => $this->leader->id,
                    'name' => $this->leader->name . ' ' . $this->leader->surname,
                    'role' => $this->leader->role?->value,
                    'avatar' => match (true) {
                        empty($this->leader->avatar_path) => null,
                        filter_var($this->leader->avatar_path, FILTER_VALIDATE_URL) => $this->leader->avatar_path,
                        default => Storage::disk('public')->url($this->leader->avatar_path),
                    },

                    'chat_id' => $this->leaderChat?->id,
                ];
            }),

            $this->mergeWhen($isLeader, function () use ($todayStr) {
                $players = $this->players()
                    ->withSum('contacts as total_volume', 'volume')
                    ->with(['checklists' => fn($q) => $q->latest('date')])
                    ->get();

                $teamVolume = (int)$players->sum('total_volume');

                $members = $players->map(function ($player) use ($todayStr) {
                    $lastChecklist = $player->checklists->first();
                    $todayChecklist = $player->checklists->first(fn($c) => $c->date->toDateString() === $todayStr);

                    if ($todayChecklist) {
                        $currentDayNumber = $todayChecklist->day_number;
                    } else {
                        $maxDay = $player->checklists->max('day_number') ?? 0;
                        $currentDayNumber = $maxDay + 1;
                    }

                    $progressPercent = min(100, max(0, round(($currentDayNumber / 90) * 100)));

                    $status = 'Inactive';
                    if ($lastChecklist && $lastChecklist->is_completed && !($lastChecklist->is_day_off ?? false)) {
                        $status = 'Active';
                    }

                    return [
                        'id' => $player->id,
                        'name' => $player->name . ' ' . $player->surname,
                        'avatar' => $player->avatar_url ?? null,
                        'current_day_number' => $currentDayNumber,
                        'progress' => $progressPercent,
                        'status' => $status,
                    ];
                });

                return [
                    'team_volume' => $teamVolume,
                    'team_members' => $members,
                ];
            }),

            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
