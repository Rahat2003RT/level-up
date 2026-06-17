<?php

namespace App\Http\Controllers\Api\v1\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\Auth\LoginRequest;
use App\Http\Requests\Guest\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Guest\Auth\AuthService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Response;

#[Group('Авторизация', weight: 10)]
final class AuthController extends Controller
{
    public function __construct(
        protected AuthService $service
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
}
