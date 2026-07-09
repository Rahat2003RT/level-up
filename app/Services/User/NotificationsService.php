<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
