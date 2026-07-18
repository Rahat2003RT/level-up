<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Создаем событие удаления. Передаем id чата и id удаленного сообщения.
     */
    public function __construct(
        public int    $chatId,
        public string $messageId
    )
    {
    }

    /**
     * Каналы, в которые должно транслироваться событие.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chats.' . $this->chatId),
        ];
    }

    /**
     * Имя события для фронтенда.
     */
    public function broadcastAs(): string
    {
        return 'message.deleted';
    }
}
