<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Отметить входящие сообщения прочитанными.
     */
    public function markAsRead(Chat $chat, User $user): int
    {
        $unreadQuery = $chat->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at');

        $unreadCount = $unreadQuery->count();

        if ($unreadCount > 0) {
            $unreadQuery->update(['read_at' => now()]);
            // broadcast(new MessagesRead($chat->id, $user->id))->toOthers();
        }

        return $unreadCount;
    }
}
