<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Admin\Auth\AuthService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Авторизация', weight: 0)]
final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $service
    ) {}

    /**
     * Авторизация админа
     * @param LoginRequest $request
     * @return UserResource
     */
    public function login(LoginRequest $request): UserResource
    {
        $user = $this->service->login($request->validated());
        return UserResource::make($user);
    }
}
