<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
final class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'chat_id'    => $this->chat_id,
            'sender_id'  => $this->sender_id,
            'text'       => $this->text,
            'read_at'    => $this->read_at?->toIso8601String(),
            'is_edited'  => (bool) $this->is_edited,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
