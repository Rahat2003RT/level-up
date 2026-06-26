<?php

namespace App\Services\Guest\Auth;

use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
            'name' => $data['name'],
            'locale' => $data['locale'] ?? 'en',
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

    public function sendResetCode(array $data): void
    {
        $email = $data['email'];
        $user = User::where('email', $email)->firstOrFail();

        $locale = request()->header('X-Locale', request()->input('lang', $user->locale ?? 'en'));

        $code = (string) rand(100000, 999999);

        DB::table('password_reset_codes')->where('email', $email)->delete();
        DB::table('password_reset_codes')->insert([
            'email' => $email,
            'code' => $code,
            'created_at' => now(),
        ]);

        $user->notify(new CustomResetPasswordNotification($code, $locale));
    }

    /**
     * @throws ValidationException
     */
    public function verifyResetCode(array $data): void
    {
        $record = DB::table('password_reset_codes')
            ->where('email', $data['email'])
            ->where('code', $data['code'])
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'code' => [__('passwords.code_invalid_or_expired')],
            ]);
        }

        if (now()->subMinutes(15)->gt($record->created_at)) {
            DB::table('password_reset_codes')->where('email', $data['email'])->delete();
            throw ValidationException::withMessages([
                'code' => [__('passwords.code_invalid_or_expired')],
            ]);
        }
    }

    /**
     * 3. Непосредственно обновление пароля
     * @throws ValidationException
     */
    public function resetPassword(array $data): void
    {
        $this->verifyResetCode([
            'email' => $data['email'],
            'code' => $data['code']
        ]);
        /** @var User $user */
        $user = User::where('email', $data['email'])->firstOrFail();
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_codes')->where('email', $data['email'])->delete();

        Log::channel('auth')->info('User successfully reset password', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip()
        ]);
    }
}
