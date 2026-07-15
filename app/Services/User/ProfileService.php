<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        return $user->fresh(['goal', 'deviceTokens', 'leader']);
    }

    public function deleteAccount(User $user): void
    {
        $user->delete();
    }

    /**
     * @param User $user
     * @return User
     */
    public function getInfoAboutMe(User $user): User
    {
        return $user->load([
            'goal',
            'deviceTokens',
            'leader'
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function changePassword(User $user, array $data): void
    {
        if (!Hash::check($data['old_password'], $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Current password is incorrect.'],
            ]);
        }
        $user->password = Hash::make($data['new_password']);
        $user->save();
    }

    /**
     * Обновить или создать цели для пользователя.
     *
     * @param User $user
     * @param array $data
     * @return Model
     */
    public function updateOrCreateGoal(User $user, array $data): Model
    {
        return $user->goal()->updateOrCreate(
            [],
            $data
        );
    }
}
