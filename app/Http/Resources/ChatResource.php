<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Chat
 */
final class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'leader'       => [
                'id'         => $this->leader->id,
                'name'       => $this->leader->name,
                'nickname'   => $this->leader->nickname,
                'avatar_path'=> $this->leader->avatar_path,
            ],
            'last_message' => MessageResource::make($this->whenLoaded('lastMessage')),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
