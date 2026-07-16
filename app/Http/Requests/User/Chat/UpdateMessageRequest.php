<?php

declare(strict_types=1);

namespace App\Http\Requests\User\Chat;

use App\Http\Requests\ApiBaseRequest;
use App\Models\Chat;
use App\Models\Message;

final class UpdateMessageRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        $message = $this->route('message');
        $chat = $this->route('chat');

        // Если прилетели ID вместо моделей, принудительно достаем их из БД
        if (!$message instanceof Message) {
            $message = Message::find($message);
        }

        if (!$chat instanceof Chat) {
            $chat = Chat::find($chat);
        }

        // Если какую-то из моделей не нашли — доступ закрыт
        if (!$message || !$chat) {
            return false;
        }

        // Сверяем принадлежность сообщения к чату и автора сообщения
        return $message->chat_id === $chat->id
            && $message->sender_id === (int) $this->user()?->id;
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
