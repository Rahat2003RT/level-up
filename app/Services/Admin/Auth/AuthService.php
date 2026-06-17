<?php

declare(strict_types=1);

namespace App\Services\Admin\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final readonly class AuthService
{
    /**
     * Выполняет вход администратора и генерирует токен.
     *
     * @throws ValidationException
     */
    public function login(array $data): User
    {
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.']
            ]);
        }

        /** @var User $user */
        $user = auth()->user();

        if ($user->role != 'admin') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Access forbidden.']
            ]);
        }

        if ($user->blocked_at) {
            Auth::logout();
            $reason = $user->block_reason ?? 'No reason provided';
            throw ValidationException::withMessages([
                'email' => ["Your account is blocked. Reason: {$reason}"]
            ]);
        }

        $token = $user->createToken('admin_panel')->plainTextToken;
        $user->setAttribute('token', $token);
        return $user;
    }
}
