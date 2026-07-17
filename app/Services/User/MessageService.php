<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\MessageUpdated;
use App\Jobs\SendChatMessageNotification;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class MessageService
{
    /**
     * @param Chat $chat
     * @param array{around_id?: string, after_id?: string, cursor?: string} $params
     * @return Collection<int, Message>|CursorPaginator
     */
    public function getMessages(Chat $chat, array $params): Collection|CursorPaginator
    {
        $query = $chat->messages();

        if (!empty($params['around_id'])) {
            $targetMessage = Message::find($params['around_id']);

            if ($targetMessage) {
                return $query->where('created_at', '<=', $targetMessage->created_at)
                    ->orderBy('created_at', 'desc')
                    ->limit(30)
                    ->get();
            }
        }

        if (!empty($params['after_id'])) {
            $targetMessage = Message::find($params['after_id']);

            if ($targetMessage) {
                return $query->where('created_at', '>', $targetMessage->created_at)
                    ->orderBy('created_at', 'asc')
                    ->limit(100)
                    ->get();
            }
        }

        return $query->orderBy('created_at', 'desc')->cursorPaginate(30);
    }

    public function markAsRead(Chat $chat, User $user): int
    {
        $unreadQuery = $chat->messages()
            ->whereNull('read_at');
        $unreadMessageIds = $unreadQuery->pluck('id')->toArray();
        $unreadCount = count($unreadMessageIds);
        if ($unreadCount > 0) {
            $unreadQuery->update(['read_at' => now()]);
            broadcast(new MessagesRead($chat->id, $user->id, $unreadMessageIds))->toOthers();
        }
        return $unreadCount;
    }

    /**
     * Создать новое сообщение (Отправка).
     */
    public function storeMessage(Chat $chat, User $user, array $data): Message
    {
        $redisKey = "chat_online:$chat->id";
        $onlineUserIds = Redis::zrangebyscore($redisKey, (string)(time() - 30), '+inf');
        $otherOnlineUsers = array_diff($onlineUserIds, [(string)$user->id]);
        $readAt = !empty($otherOnlineUsers) ? now() : null;

        $message = $chat->messages()->create([
            'sender_id' => $user->id,
            'text'      => $data['text'] ?? null,
            'read_at'   => $readAt,
        ]);

        $message->load(['sender']);

        $recipientId = ($message->sender_id == $chat->elite_id)
            ? $chat->leader_id
            : $chat->elite_id;

        broadcast(new MessageSent($message, (int)$recipientId))->toOthers();

        SendChatMessageNotification::dispatch($message);
        return $message;
    }

    public function updateMessage(Message $message, array $data): Message
    {
        $message->update(['text' => $data['text'] ?? $message->text,]);
        $message->load(['sender']);
        broadcast(new MessageUpdated($message))->toOthers();
        return $message;
    }
}
