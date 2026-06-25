<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\Auth\LoginRequest;
use App\Http\Requests\Guest\Auth\RegisterRequest;
use App\Http\Requests\Guest\Auth\ForgotPasswordRequest;
use App\Http\Requests\Guest\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\Guest\Auth\AuthService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

#[Group('Авторизация', weight: 10)]
final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $service
    )
    {
    }

    /**
     * Регистрация
     * @param RegisterRequest $request
     * @return UserResource
     */
    public function register(RegisterRequest $request): UserResource
    {
        $user = $this->service->register($request->validated());
        return UserResource::make($user);
    }

    /**
     * Авторизация
     * @param LoginRequest $request
     * @return UserResource
     * @throws ValidationException
     */
    public function login(LoginRequest $request): UserResource
    {
        $user = $this->service->login($request->validated());
        return UserResource::make($user);
    }

    /**
     * Выход
     * @return Response
     */
    public function logout(): Response
    {
        $this->service->logout();
        return response()->noContent();
    }

    /**
     * Запрос ссылки на сброс пароля (Resend)
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->service->sendResetLink($request->validated());

        return response()->json([
            'message' => __($status)
        ], $status === 'passwords.sent' ? 200 : 400);
    }

    /**
     * Сброс пароля
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->service->resetPassword($request->validated());

        return response()->json([
            'message' => __($status)
        ], $status === 'passwords.reset' ? 200 : 400);
    }
}
