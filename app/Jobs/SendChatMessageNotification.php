<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Services\Mail\Firebase\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

final class SendChatMessageNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Создает новый экземпляр задачи.
     */
    public function __construct(
        public readonly Message $message
    )
    {
    }

    /**
     * Выполняет задачу отправки push-уведомления.
     */
    public function handle(FcmService $fcmService): void
    {
        $chat = $this->message->chat;
        $sender = $this->message->sender;
        $recipientId = ($this->message->sender_id === $chat->elite_id)
            ? $chat->leader_id
            : $chat->elite_id;

        $redisKey = "chat_online:{$chat->id}";
        $score = Redis::zscore($redisKey, (string)$recipientId);
        $isOnlineInChat = (int)$score >= (time() - 30);

        if ($isOnlineInChat) {
            return;
        }

        /** @var User|null $recipient */
        $recipient = User::where('id', $recipientId)
            ->where('notifications_enabled', true)
            ->with('deviceTokens')
            ->first();

        if (!$recipient || $recipient->deviceTokens->isEmpty()) {
            return;
        }

        $userDict = [
            'ru' => 'Пользователь', 'en' => 'User', 'es' => 'Usuario',
            'pt' => 'Usuário', 'fr' => 'Utilisateur', 'de' => 'Benutzer'
        ];

        $loc = $recipient->locale ?? 'en';
        if (!array_key_exists($loc, $userDict)) {
            $loc = 'en';
        }
        $senderName = trim((string)$sender->name);
        if (empty($senderName)) {
            $senderName = $userDict[$loc] . ' ' . $sender->id;
        }

        $bodyText = trim((string)$this->message->text);

        if (empty($bodyText)) {
            $bodyText = '...';

            foreach ($recipient->deviceTokens as $deviceToken) {
                $fcmService->sendToToken(
                    token: $deviceToken->token,
                    title: $senderName,
                    body: $bodyText,
                    data: [
                        'action'  => 'CHAT',
                        'chat_id' => (string)$chat->id,
                        'type'    => 'new_message'
                    ]
                );
            }
        }
    }
}
