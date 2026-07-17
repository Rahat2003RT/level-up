<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

final class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly int $recipientId;

    /**
     * @param Message $message
     * @param int $recipientId Передаем вычисленный ID получателя прямо сюда
     */
    public function __construct(
        public readonly Message $message,
        int $recipientId
    ) {
        $this->recipientId = $recipientId;
    }

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PresenceChannel("chat.{$this->message->chat_id}"),
        ];

        $score = Redis::zscore(
            "chat_online:{$this->message->chat_id}",
            (string)$this->recipientId
        );

        $isOnlineInChat = $score !== null && (int)$score >= (time() - 30);

        if (!$isOnlineInChat) {
            $channels[] = new PrivateChannel("user.{$this->recipientId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing([
            'sender.role',
            'chat'
        ]);

        return MessageResource::make($this->message)->resolve();
    }
}
