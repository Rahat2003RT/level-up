<?php

declare(strict_types=1);

namespace App\Services\Admin\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        // Ищем пользователя напрямую в БД по никнейму
        /** @var User|null $user */
        $user = User::where('nickname', $data['nickname'])->first();

        // Проверяем хэш пароля вручную
        if (!$user || !\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'nickname' => ['Invalid credentials.']
            ]);
        }

        // Теперь $user — это 100% чистая модель из БД, проверяем роль:
        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'nickname' => ['Access forbidden.']
            ]);
        }

        if ($user->blocked_at) {
            $reason = $user->block_reason ?? 'No reason provided';
            throw ValidationException::withMessages([
                'nickname' => ["Your account is blocked. Reason: {$reason}"]
            ]);
        }

        $token = $user->createToken('admin_panel')->plainTextToken;
        $user->setAttribute('token', $token);

        return $user;
    }
}
