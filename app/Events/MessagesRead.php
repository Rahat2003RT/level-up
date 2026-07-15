<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MessagesRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $chatId ID чата, в котором прочитаны сообщения.
     * @param int $readByUserId ID пользователя, который прочитал сообщения.
     * @param array<string> $messageIds Массив UUID прочитанных сообщений.
     */
    public function __construct(
        public readonly int $chatId,
        public readonly int $readByUserId,
        public readonly array $messageIds
    ) {}

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("chat.$this->chatId"),
        ];
    }

    /**
     * @noinspection PhpUnused
     */
    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    /**
     * @return array{chat_id: int, read_by_user_id: int, message_ids: array<string>}
     * @noinspection PhpUnused
     */
    public function broadcastWith(): array
    {
        return [
            'chat_id'         => $this->chatId,
            'read_by_user_id' => $this->readByUserId,
            'message_ids'     => $this->messageIds,
        ];
    }
}
