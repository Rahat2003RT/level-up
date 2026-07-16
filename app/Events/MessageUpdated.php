<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Message $message
     */
    public function __construct(
        public readonly Message $message
    ) {}

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("chat.{$this->message->chat_id}"),
        ];
    }

    /**
     * @noinspection PhpUnused
     */
    public function broadcastAs(): string
    {
        return 'message.updated';
    }

    /**
     * @return array{data: array<string, mixed>}
     * @noinspection PhpUnused
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing([
            'sender',
        ]);

        return MessageResource::make($this->message)->resolve();
    }
}
