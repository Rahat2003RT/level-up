<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Http\Requests\ApiBaseRequest;
use App\Models\Chat;

final class ChatAccessRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        /** @var Chat|null $chat */
        $chat = $this->route('chat');
        $user = $this->user();

        if (!$chat || !$user) {
            return false;
        }

        return $chat->elite_id === $user->id || $chat->leader_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'around_id' => ['nullable', 'string', 'uuid'],
            'after_id'  => ['nullable', 'string', 'uuid'],
            'cursor'    => ['nullable', 'string'],
        ];
    }
}
