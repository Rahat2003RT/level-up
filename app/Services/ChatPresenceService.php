<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

final class ChatPresenceService
{
    private const string PRESENCE_PREFIX = 'chat:';
    private const string PRESENCE_SUFFIX = ':user:';
    private const int TTL = 30;

    /**
     * Стандартизированный ключ для Redis.
     * Пример ключа: "chat:12:user:5"
     */
    private function getKey(int $chatId, int $userId): string
    {
        return self::PRESENCE_PREFIX . $chatId . self::PRESENCE_SUFFIX . $userId;
    }

    /**
     * PING: Пользователь сообщает, что он открыл чат или всё ещё находится в нём.
     */
    public function ping(Chat $chat, User $user): void
    {
        $key = $this->getKey($chat->id, $user->id);

        Redis::set($key, '1', 'EX', self::TTL);
    }

    /**
     * Пользователь явно вышел из чата (закрыл окно переписки).
     */
    public function leave(Chat $chat, User $user): void
    {
        $key = $this->getKey($chat->id,$user->id);
        Redis::del($key);
    }
}
