<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Models\UserGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'id'         => $this->id,
            'account_id' => $this->account_id,
            'leader_id'  => $this->leader_id,
            'name'       => $this->name,
            'surname'    => $this->surname,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'avatar'     => $this->avatar,

            'country'               => $this->country,
            'city'                  => $this->city,
            'company_name'          => $this->company_name,
            'timezone'              => $this->timezone,
            'date_of_birth'         => $this->date_of_birth,
            'notifications_enabled' => $this->notifications_enabled,
            'locale'                => $this->locale,

            'role'         => $this->role?->value,
            'is_onboarded' => $this->is_onboarded,

            'tariff_id'               => $this->tariff_id,
            'tariff'                  => TariffResource::make($this->whenLoaded('tariff')),
            'on_trial'                => $this->onTrial(),
            'has_active_subscription' => $this->hasActiveSubscription(),
            'auto_renew'              => $this->auto_renew,
            'plan_paused'             => $this->plan_paused,

            'trial_started_at'     => $this->trial_started_at?->toIso8601String(),
            'trial_ends_at'        => $this->trial_ends_at?->toIso8601String(),
            'subscription_ends_at' => $this->subscription_ends_at?->toIso8601String(),

            'is_blocked' => !is_null($this->blocked_at),
            'is_deleted' => $this->trashed(),

            $this->mergeWhen(!is_null($this->blocked_at), [
                'blocked_at'   => $this->blocked_at?->toIso8601String(),
                'block_reason' => $this->block_reason,
            ]),

            $this->mergeWhen($this->trashed(), [
                'deleted_at' => $this->deleted_at?->toIso8601String(),
            ]),

            'token' => $this->when(!empty($this->token), $this->token),

            'device_tokens' => $this->whenLoaded('deviceTokens', fn() => $this->deviceTokens->pluck('token')),

            'goal' => UserGoalResource::make($this->whenLoaded('goal')),

            'team' => $this->whenLoaded('leader', function () {
                return [
                    'id'      => $this->leader->id,
                    'name'    => "{$this->leader->name} {$this->leader->surname}",
                    'role'    => $this->leader->role?->value,
                    'avatar'  => $this->leader->avatar,
                    'chat_id' => $this->leaderChat?->id,
                ];
            }),

            $this->mergeWhen($this->role?->value === 'leader', fn() => [
                'team_volume'  => (int)$this->whenLoaded('players', fn() => $this->players->sum('total_volume'), 0),
                'team_members' => TeamMemberResource::collection($this->whenLoaded('players')),
            ]),

            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
