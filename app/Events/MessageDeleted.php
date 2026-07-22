<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel; // Используем PresenceChannel как в MessageSent
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Создаем событие удаления.
     */
    public function __construct(
        public int $chatId,
        public string $messageId,
        public bool $isLastMessage = false
    ) {}

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        $chat = Chat::find($this->chatId);
        $channels = [new PresenceChannel("chat.{$this->chatId}"),];

        if (!$chat) {return $channels;}
        if ($this->isLastMessage) {
            $channels[] = new PrivateChannel("user.{$chat->leader_id}");
            $channels[] = new PrivateChannel("user.{$chat->elite_id}");
        }
        return $channels;
    }

    /**
     * Имя события для фронтенда.
     */
    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    /**
     * Данные, которые улетят на фронт.
     */
    public function broadcastWith(): array
    {
        $chat = Chat::find($this->chatId);
        $nextLastMessage = $chat?->messages()->latest()->first();
        return [
            'id'      => $this->messageId,
            'chat_id' => $this->chatId,
            'is_last_message' => $this->isLastMessage,
            'next_last_text'  => $nextLastMessage?->text,
        ];
    }
}
