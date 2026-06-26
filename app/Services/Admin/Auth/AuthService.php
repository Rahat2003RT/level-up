<?php

declare(strict_types=1);

namespace App\Services\Admin\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class AuthService
{
    /**
     * @throws ValidationException
     */
    public function login(array $data): User
    {
        /** @var User|null $user */
        $user = User::query()->where('nickname', $data['nickname'])->first();

        if (!$user || !$user->isAdmin() || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['nickname' => ['Invalid credentials.']]);
        }
        if ($user->blocked_at) {
            $reason = $user->block_reason ?? 'No reason provided';
            throw ValidationException::withMessages(['nickname' => ["Your account is blocked. Reason: $reason"]]);
        }
        $token = $user->createToken('admin_panel')->plainTextToken;
        $user->token = $token;
        return $user;
    }
}
