<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class NotificationsService
{

    public function getNotifications(User $user): LengthAwarePaginator
    {
        $notifications = $user->notifications()->latest()->paginate(20);
        $user->notifications()->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $notifications;
    }

    public function getUnreadCount(User $user): int
    {
        return $user->notifications()
            ->where('is_read', false)
            ->count();
    }
}
