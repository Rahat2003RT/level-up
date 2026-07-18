<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel; // Используем PresenceChannel как в MessageSent
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Создаем событие удаления.
     */
    public function __construct(
        public int $chatId,
        public string $messageId
    ) {}

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("chat.{$this->chatId}"),
        ];
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
        return [
            'id'      => $this->messageId,
            'chat_id' => $this->chatId,
        ];
    }
}
