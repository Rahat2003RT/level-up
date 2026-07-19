<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Events\MessageDeleted;
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
                return $query->where(function ($q) use ($targetMessage) {
                    $q->where('created_at', '<', $targetMessage->created_at)
                        ->orWhere(function ($sub) use ($targetMessage) {
                            $sub->where('created_at', '=', $targetMessage->created_at)
                                ->where('id', '<=', $targetMessage->id);
                        });
                })
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->limit(30)
                    ->get();
            }
        }

        if (!empty($params['after_id'])) {
            $targetMessage = Message::find($params['after_id']);

            if ($targetMessage) {
                return $query->where(function ($q) use ($targetMessage) {
                    $q->where('created_at', '>', $targetMessage->created_at)
                        ->orWhere(function ($sub) use ($targetMessage) {
                            $sub->where('created_at', '=', $targetMessage->created_at)
                                ->where('id', '>', $targetMessage->id);
                        });
                })
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->limit(100)
                    ->get();
            }
        }

        return $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->cursorPaginate(30);
    }

    public function markAsRead(Chat $chat, User $user): int
    {
        $unreadQuery = $chat->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at');

        $unreadMessageIds = $unreadQuery->pluck('id')->toArray();
        $unreadCount = count($unreadMessageIds);

        if ($unreadCount > 0) {
            $unreadQuery->update(['read_at' => now()]);
            broadcast(new MessagesRead($chat->id, $user->id, $unreadMessageIds))->toOthers();
        }

        return $unreadCount;
    }

    public function storeMessage(Chat $chat, User $user, array $data): Message
    {
        // Определяем ID получателя
        $recipientId = ($user->id == $chat->elite_id)
            ? $chat->leader_id
            : $chat->elite_id;

        // Проверяем онлайн получателя по структуре ключей из ChatPresenceService ("chat:{id}:user:{id}")
        $recipientRedisKey = "chat:{$chat->id}:user:{$recipientId}";
        $isRecipientOnline = (bool)Redis::exists($recipientRedisKey);

        $readAt = $isRecipientOnline ? now() : null;

        $message = $chat->messages()->create([
            'sender_id' => $user->id,
            'text'      => $data['text'] ?? null,
            'read_at'   => $readAt,
        ]);

        $message->load(['sender']);

        broadcast(new MessageSent($message, (int)$recipientId))->toOthers();

        SendChatMessageNotification::dispatch($message);

        return $message;
    }

    public function updateMessage(Message $message, array $data): Message
    {

        $message->update([
            'text' => $data['text'] ?? $message->text,
            'is_edited' => true,
        ]);

        $message->refresh();
        $message->load(['sender']);

        broadcast(new MessageUpdated($message))->toOthers();

        return $message;
    }

    public function deleteMessage(Message $message): void
    {
        // Обязательно подгружаем отношение, если его нет в памяти
        $chat = $message->chat ?? Chat::find($message->chat_id);
        $chatId = $message->chat_id;
        $messageId = $message->id;

        // ИСПРАВЛЕНО: явно передаем 'created_at' для сортировки UUID моделей
        $lastMessageId = $chat->messages()
            ->latest('created_at')
            ->value('id');

        $isLast = ($messageId === $lastMessageId);

        $message->delete();

        broadcast(new MessageDeleted($chatId, $messageId, $isLast))->toOthers();
    }
}
