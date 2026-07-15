<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    private string $projectId = 'wellness-72287';
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $factory = (new Factory)->withServiceAccount(storage_path('app/firebase/Wellness-Coach-SDK.json'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $messaging->send($message);
            return true;
        } catch (\Exception $e) {
            \Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }
}
