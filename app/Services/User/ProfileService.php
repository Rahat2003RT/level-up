<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        if (!empty($user->role)) {
            unset($data['role']);
        }
        $user->update($data);
        return $user;
    }
    public function deleteAccount(): void
    {
        $user = auth()->user();
        $user->delete();
    }

    public function getNotifications(User $user, array $data): HasMany
    {
        return $user->notifications();
    }
}
