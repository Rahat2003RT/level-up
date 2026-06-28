<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

final class ProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            if ($user->avatar_path && !str_starts_with($user->avatar_path, 'http')) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = $data['avatar']->store('avatars', 'public');
            unset($data['avatar']);
        }

        $token = $data['device_token'] ?? null;
        unset($data['device_token']);

        $user->update($data);

        if ($token) {
            UserDevice::where('token', $token)
                ->where('user_id', '!=', $user->id)
                ->delete();

            $user->deviceTokens()->updateOrCreate(
                ['token' => $token],
                ['token' => $token]
            );
        }

        return $user->fresh(['deviceTokens']);
    }

    public function deleteAccount(User $user): void
    {
        $user->delete();
    }

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

    /**
     * @param User $user
     * @return User
     */
    public function getInfoAboutMe(User $user): User
    {
        return $user->load([
            'goal',
            'deviceTokens'
        ]);
    }

    public function changePassword(User $user, array $data): void
    {
        $user->password = Hash::make($data['password']);
    }
}
