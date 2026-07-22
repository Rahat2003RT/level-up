<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\BlockUserRequest;
use App\Http\Requests\Admin\Users\ChangeRoleRequest;
use App\Http\Requests\Admin\Users\ChangeUserRequest;
use App\Http\Requests\Admin\Users\CreateUserRequest;
use App\Http\Requests\Admin\Users\IndexUserRequest;
use App\Http\Requests\Admin\Users\ToggleTrialRequest;
use App\Http\Requests\User\Statistics\IndexRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserStatisticsResource;
use App\Models\User;
use App\Services\Admin\UserService;
use App\Services\User\PlanService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

#[Group('Пользователь / Админка', weight: 10)]
final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $service,
        private readonly PlanService $planService
    )
    {
    }

    /**
     * Список пользователей
     * @param IndexUserRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $users = $this->service->getUsers($request->validated());
        return UserResource::collection($users);
    }

    /**
     * Создание нового пользователя
     * @param CreateUserRequest $request
     * @return UserResource
     */
    public function store(CreateUserRequest $request): UserResource
    {
        $user = $this->service->createUser($request->validated());
        return UserResource::make($user);
    }

    /**
     * О пользователе
     * @param User $user
     * @return UserResource
     */
    public function show(User $user): UserResource
    {
        return UserResource::make($user->loadMissing(['deviceTokens', 'goal', 'leader', 'tariff']));
    }

    /**
     * Изменение роли пользователя
     * @param ChangeRoleRequest $request
     * @param User $user
     * @return UserResource
     * @throws ValidationException
     */
    public function changeRole(ChangeRoleRequest $request, User $user): UserResource
    {
        $updatedUser = $this->service->changeRole($user, $request->validated('role'));
        return UserResource::make($updatedUser);
    }

    /**
     * Редактирование пользователя
     * @param ChangeUserRequest $request
     * @param User $user
     * @return UserResource
     */
    public function update(ChangeUserRequest $request, User $user): UserResource
    {
        $updatedUser = $this->service->changeUser($user, $request->validated());
        return UserResource::make($updatedUser);
    }

    /**
     * Блокировка пользователя
     * @param BlockUserRequest $request
     * @param User $user
     * @return UserResource
     */
    public function block(BlockUserRequest $request, User $user): UserResource
    {
        $updatedUser = $this->service->block(
            $user,
            $request->validated('block_reason')
        );
        return UserResource::make($updatedUser);
    }

    /**
     * Разблокировка пользователя
     * @param User $user
     * @return UserResource
     */
    public function unblock(User $user): UserResource
    {
        $updatedUser = $this->service->unblock($user);
        return UserResource::make($updatedUser);
    }

    /**
     * Удаление пользователя
     * @param User $user
     * @return Response
     */
    public function destroy(User $user): Response
    {
        $this->service->destroy($user);
        return response()->noContent();
    }

    /**
     * Восстановление пользователя
     * @param User $user
     * @return UserResource
     * @throws ValidationException
     */
    public function restore(User $user): UserResource
    {
        $user = $this->service->restore($user);
        return UserResource::make($user);
    }

    /**
     * Окончательное удаление
     * @param User $user
     * @return Response
     */
    public function forceDelete(User $user): Response
    {
        $this->service->forceDelete($user);
        return response()->noContent();
    }

    /**
     * Выдать (на 7 дней) или забрать пробный период
     *
     * @param ToggleTrialRequest $request
     * @param User $user
     * @return UserResource
     */
    public function toggleTrial(ToggleTrialRequest $request, User $user): UserResource
    {
        $updatedUser = $this->service->toggleTrial(
            $user,
            (bool) $request->validated('is_trial')
        );
        return UserResource::make($updatedUser);
    }

    /**
     * Статистика конкретного пользователя для администратора
     *
     * @param IndexRequest $request (или отдельный AdminIndexRequest)
     * @param User $user
     * @return UserStatisticsResource
     */
    public function statistics(IndexRequest $request, User $user): UserStatisticsResource
    {
        $stats = $this->planService->getStatisticsForUser($user, $request->validated());
        return UserStatisticsResource::make($stats);
    }
}
