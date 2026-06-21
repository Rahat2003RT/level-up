<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }
    public function deleteAccount(User $user): void
    {
        $user->delete();
    }

    public function getNotifications(User $user): LengthAwarePaginator
    {
        $notifications = $user->notifications()->latest()->paginate(20);
        $unreadIds = $notifications->where('is_read', false)->pluck('id');
        if ($unreadIds->isNotEmpty()) {
            $user->notifications()->whereIn('id', $unreadIds)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
        return $notifications;
    }
}
