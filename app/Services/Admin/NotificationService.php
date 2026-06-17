<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use App\Models\UserDeviceToken;
use App\Models\UserNotification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

final readonly class NotificationService
{
    public function __construct(
        private Messaging $messaging
    ) {}

    /**
     * Оптимизированная массовая рассылка (без удержания статистики в памяти).
     */
    public function sendMassPush(array $data): void
    {
        $title = $data['title'] ?? '';
        $description = $data['body'] ?? '';
        $notification = Notification::create($title, $description);

        User::query()->chunkById(500, function ($users) use ($title, $description, $notification) {
            $dbPayload = [];

            foreach ($users as $user) {
                $dbPayload[] = [
                    'user_id'     => $user->id,
                    'title'       => $title,
                    'description' => $description,
                    'is_read'     => false,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            if (!empty($dbPayload)) {
                UserNotification::insert($dbPayload);
            }

            $tokens = UserDeviceToken::whereIn('user_id', $users->pluck('id'))
                ->pluck('token')
                ->toArray();

            if (empty($tokens)) {
                return;
            }

            $message = CloudMessage::new()->withNotification($notification);

            try {
                $report = $this->messaging->sendMulticast($message, $tokens);

                $invalidTokens = array_merge($report->invalidTokens(), $report->unknownTokens());

                if (!empty($invalidTokens)) {
                    UserDeviceToken::whereIn('token', $invalidTokens)->delete();
                }
            } catch (Throwable $e) {
                logger()->error('FCM Mass Push Chunk Error: ' . $e->getMessage());
            }
        });
    }
}
