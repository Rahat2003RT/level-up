<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Admin\AuthService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Validation\ValidationException;

#[Group('Авторизация / Админка', weight: 0)]
final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $service
    ) {}

    /**
     * Авторизация админа
     * @param LoginRequest $request
     * @return UserResource
     * @throws ValidationException
     */
    public function login(LoginRequest $request): UserResource
    {
        $user = $this->service->login($request->validated());
        return UserResource::make($user);
    }
}
