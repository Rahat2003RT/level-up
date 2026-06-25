<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\BlockUserRequest;
use App\Http\Requests\Admin\Users\ChangeRoleRequest;
use App\Http\Requests\Admin\Users\ChangeUserRequest;
use App\Http\Requests\Admin\Users\CreateUserRequest;
use App\Http\Requests\Admin\Users\IndexUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Admin\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Пользователь / Админка', weight: 10)]
final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $service
    ) {}

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
     * Создание нового пользователя администратором
     * @param CreateUserRequest $request
     * @return UserResource
     */
    public function store(CreateUserRequest $request): UserResource
    {
        $user = $this->service->createUser($request->validated());
        return UserResource::make($user);
    }

    /**
     * Показать пользователя
     * @param User $user
     * @return UserResource
     */
    public function show(User $user): UserResource
    {
        return UserResource::make($user->loadMissing(['deviceTokens']));
    }

    /**
     * Изменение роли пользователя
     * @param ChangeRoleRequest $request
     * @param User $user
     * @return UserResource
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
    public function changeUser(ChangeUserRequest $request, User $user): UserResource
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
     */
    public function restore(User $user): UserResource
    {
        $user = $this->service->restore($user->id);

        return UserResource::make($user);
    }

    /**
     * Окончательное удаление
     * @param int $id
     * @return Response
     */
    public function forceDelete(int $id): Response
    {
        $this->service->forceDelete($id);
        return response()->noContent();
    }
}
