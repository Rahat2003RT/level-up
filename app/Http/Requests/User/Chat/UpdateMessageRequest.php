<?php

declare(strict_types=1);

namespace App\Http\Requests\Channels\Chat\Message;

use App\Http\Requests\ApiBaseRequest;
use App\Models\Chat;
use App\Models\Message;

final class UpdateMessageRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        /** @var Message|null $message */
        $message = $this->route('message');

        /** @var Chat|null $chat */
        $chat = $this->route('chat');

        if (!$message || !$chat) {
            return false;
        }

        return $message->chat_id === $chat->id
            && $message->sender_id === (int) $this->user()->id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:5000'],
        ];
    }
}
