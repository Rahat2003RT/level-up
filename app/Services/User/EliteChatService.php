<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Auth\Access\AuthorizationException;

final class EliteChatService
{
    /**
     * Получить список чатов Elite с поиском по лидеру.
     */
    public function getChats(User $elite, array $data): LengthAwarePaginator
    {
        $search = $data['query'] ?? null;
        $limit = $data['limit'] ?? 20;

        return Chat::where('elite_id', $elite->id)
            ->with(['leader', 'lastMessage'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('leader', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('nickname', 'like', "%{$search}%");
                });
            })
            ->latest('updated_at')
            ->paginate($limit);
    }

    /**
     * Получить сообщения чата (пагинация от новых к старым).
     */
    public function getMessages(User $user, Chat $chat, int $limit = 30): LengthAwarePaginator
    {
        $this->authorizeChatAccess($user, $chat);

        return $chat->messages()
            ->latest()
            ->paginate($limit);
    }

    /**
     * Отправить новое сообщение в чат.
     */
    public function createMessage(User $user, Chat $chat, array $data): Message
    {
        $this->authorizeChatAccess($user, $chat);

        /** @var Message $message */
        $message = $chat->messages()->create([
            'id'        => $data['id'] ?? null,
            'sender_id' => $user->id,
            'text'      => $data['text'],
        ]);

        $chat->touch();

        return $message;
    }

    /**
     * Обновить сообщение.
     */
    public function updateMessage(User $user, Message $message, array $data): Message
    {
        if ($message->sender_id !== $user->id) {
            throw new AuthorizationException("You cannot edit someone else's message.");
        }

        $message->update([
            'text' => $data['text'],
        ]);

        return $message;
    }

    /**
     * Удалить сообщение (Soft Delete).
     */
    public function deleteMessage(User $user, Message $message): bool
    {
        if ($message->sender_id !== $user->id) {
            throw new AuthorizationException("You cannot delete someone else's message.");
        }

        return (bool) $message->delete();
    }

    /**
     * Проверка доступа пользователя к чату.
     */
    private function authorizeChatAccess(User $user, Chat $chat): void
    {
        if ($chat->elite_id !== $user->id && $chat->leader_id !== $user->id) {
            throw new AuthorizationException('You do not have access to this chat.');
        }
    }
}
