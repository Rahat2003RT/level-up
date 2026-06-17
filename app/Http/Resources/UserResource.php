<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Class UserResource
 * * @package App\Http\Resources
 * * @mixin User
 * @property string|null $token Токен доступа (динамически передается при авторизации)
 */
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'leader_id' => $this->leader_id,
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'phone' => $this->phone,

            'avatar_path' => match (true) {
                empty($this->avatar_path) => null,
                filter_var($this->avatar_path, FILTER_VALIDATE_URL) => $this->avatar_path,
                default => Storage::disk('public')->url($this->avatar_path),
            },

            'country' => $this->country,
            'city' => $this->city,
            'company' => $this->company,
            'timezone' => $this->timezone,

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
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
