<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\UserDevice;
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

    public function sendMassPush(array $data): void
    {
        $titles = $data['title'] ?? [];
        $descriptions = $data['description'] ?? [];

        $jsonTitle = json_encode($titles, JSON_UNESCAPED_UNICODE);
        $jsonDescription = json_encode($descriptions, JSON_UNESCAPED_UNICODE);

        $now = now();

        UserDevice::query()
            ->select(['user_devices.id', 'user_devices.user_id', 'user_devices.token', 'users.locale'])
            ->join('users', 'users.id', '=', 'user_devices.user_id')
            ->where('users.notifications_enabled', true)
            ->chunkById(500, function ($deviceTokens) use ($titles, $descriptions, $jsonTitle, $jsonDescription, $now) {

                $userIds = $deviceTokens->pluck('user_id')->unique()->toArray();
                $dbPayload = array_map(fn($userId) => [
                    'user_id'     => $userId,
                    'title'       => $jsonTitle,
                    'description' => $jsonDescription,
                    'is_read'     => false,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ], $userIds);

                UserNotification::insertOrIgnore($dbPayload);

                foreach ($deviceTokens->groupBy('locale') as $locale => $groupedTokens) {
                    $fcmTitle = $titles[$locale] ?? $titles['en'] ?? collect($titles)->first() ?? '';
                    $fcmDescription = $descriptions[$locale] ?? $descriptions['en'] ?? collect($descriptions)->first() ?? '';

                    if (empty($fcmTitle) && empty($fcmDescription)) {
                        continue;
                    }

                    $tokens = $groupedTokens->pluck('token')->filter()->toArray();
                    if (empty($tokens)) {
                        continue;
                    }

                    $message = CloudMessage::new()->withNotification(
                        Notification::create((string)$fcmTitle, (string)$fcmDescription)
                    );

                    try {
                        $report = $this->messaging->sendMulticast($message, $tokens);
                        $invalidTokens = array_merge($report->invalidTokens(), $report->unknownTokens());

                        if (!empty($invalidTokens)) {
                            UserDevice::whereIn('token', $invalidTokens)->delete();
                        }
                    } catch (Throwable $e) {
                        logger()->error("FCM Mass Push Chunk Error ($locale): " . $e->getMessage());
                    }
                }
            }, 'user_devices.id');
    }
}
