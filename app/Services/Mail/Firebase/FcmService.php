<?php

namespace App\Services\Mail\Firebase;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Log;

class FcmService
{
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $factory = (new Factory)->withServiceAccount(storage_path('app/firebase-auth.json'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }
}
