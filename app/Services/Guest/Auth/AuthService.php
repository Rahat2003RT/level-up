<?php

namespace App\Services\Guest\Auth;

use App\Enums\UserPlan;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    public function __construct(
        protected FirebaseAuth $firebaseAuth
    )
    {
    }

    /**
     * Регистрация нового пользователя
     * * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        /** @var User $user */
        $user = User::create([
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Log::channel('auth')->info('User registered successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip()
        ]);

        $user->token = $user->createToken('auth_token')->plainTextToken;

        return $user;
    }

    /**
     * Вход по email/password
     * @throws ValidationException
     */
    public function login(array $data): User
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            Log::channel('auth')->warning('Failed login attempt', [
                'email' => $data['email'],
                'ip' => request()->ip(),
                'reason' => 'Invalid credentials'
            ]);

            throw ValidationException::withMessages([
                'email' => ['Email or password is incorrect'],
            ]);
        }

        if ($user->blocked_at !== null) {
            Log::channel('auth')->warning('Blocked user attempted to login', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
                'block_reason' => $user->block_reason
            ]);
            $reason = $user->block_reason ? " Reason: $user->block_reason" : "";
            throw ValidationException::withMessages([
                'email' => ["Your account has been blocked.$reason"],
            ]);
        }

        Log::channel('auth')->info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip()
        ]);

        $user->token = $user->createToken('auth_token')->plainTextToken;

        return $user;
    }

    public function logout(): void
    {
        $user = request()->user();
        if (!$user) {
            return;
        }
        /** @var PersonalAccessToken $token */
        $token = $user->currentAccessToken();

        if ($token) {
            Log::channel('auth')->info('User logged out', [
                'user_id' => $user->id,
                'ip' => request()->ip()
            ]);
            $token->delete();
        }
    }
}
