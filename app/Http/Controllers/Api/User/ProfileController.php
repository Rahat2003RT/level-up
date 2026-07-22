<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Goal\StoreUserGoalRequest;
use App\Http\Requests\User\Profile\ChangePasswordRequest;
use App\Http\Requests\User\Profile\UpdateRequest;
use App\Http\Resources\UserResource;
use App\Services\User\ProfileService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

#[Group('Пользователь', weight: 200)]
final class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $service
    )
    {
    }

    /**
     * Инфо пользователя
     * @param Request $request
     * @return UserResource
     */
    public function me(Request $request): UserResource
    {
        $user = $this->service->getInfoAboutMe($request->user());
        return UserResource::make($user);
    }

    /**
     * Обновление пользователя
     * @param UpdateRequest $request
     * @return UserResource
     */
    public function update(UpdateRequest $request): UserResource
    {
        $user = $this->service->updateProfile($request->user(), $request->validated());
        return UserResource::make($user);
    }

    /**
     * Удаление пользователя
     * @param Request $request
     * @return Response
     */
    public function destroy(Request $request): Response
    {
        $this->service->deleteAccount($request->user());
        return response()->noContent();
    }

    /**
     * Смена пароля
     * @param ChangePasswordRequest $request
     * @return Response
     * @throws ValidationException
     */
    public function changePassword(ChangePasswordRequest $request): Response
    {
        $this->service->changePassword($request->user(), $request->validated());
        return response()->noContent();
    }

    /**
     * Сохранить или обновить цели игрока.
     * @param StoreUserGoalRequest $request
     * @return UserResource
     */
    public function storeGoal(StoreUserGoalRequest $request): UserResource
    {
        $this->service->updateOrCreateGoal($request->user(), $request->validated());
        return UserResource::make($request->user()->load('goal'));
    }
}
