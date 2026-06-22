<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Profile\UpdateRequest;
use App\Http\Resources\NotificationsResource;
use App\Http\Resources\UserResource;
use App\Services\User\ProfileService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Профиль пользователя', weight: 100)]
class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $service
    )
    {
    }

    /**
     * Инфо пользователя
     */
    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    /**
     * Список уведомлений
     */
    public function notifications(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->service->getNotifications($request->user());
        return NotificationsResource::collection($notifications);
    }

    /**
     * Количество непрочитанных уведомлений
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->service->getUnreadCount($request->user());

        return response()->json([
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Обновление пользователя
     */
    public function update(UpdateRequest $request): UserResource
    {
        $user = $this->service->updateProfile($request->user(), $request->validated());
        return UserResource::make($user);
    }

    /**
     * Удаление пользователя
     */
    public function destroy(Request $request): Response
    {
        $this->service->deleteAccount($request->user());
        return response()->noContent();
    }
}
