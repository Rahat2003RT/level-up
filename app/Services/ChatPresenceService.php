<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Redis;

final class ChatPresenceService
{
    private const PRESENCE_PREFIX = 'chat:';
    private const PRESENCE_SUFFIX = ':user:';
    private const TTL = 30; // Время, через которое юзер считается "вышедшим" из чата (в сек)

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
    public function ping(int $chatId, int $userId): void
    {
        $key = $this->getKey($chatId, $userId);

        Redis::set($key, '1', 'EX', self::TTL);
    }

    /**
     * PONG: Проверяем, находится ли конкретный пользователь в данном чате прямо сейчас.
     */
    public function isUserInChat(int $chatId, int $userId): bool
    {
        $key = $this->getKey($chatId, $userId);

        return (bool) Redis::exists($key);
    }

    /**
     * Пользователь явно вышел из чата (закрыл окно переписки).
     */
    public function leave(int $chatId, int $userId): void
    {
        $key = $this->getKey($chatId, $userId);
        Redis::del($key);
    }
}
