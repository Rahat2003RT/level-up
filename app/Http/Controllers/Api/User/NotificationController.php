<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Goal\StoreUserGoalRequest;
use App\Http\Requests\User\Profile\ChangePasswordRequest;
use App\Http\Requests\User\Profile\UpdateRequest;
use App\Http\Resources\NotificationsResource;
use App\Http\Resources\UserResource;
use App\Services\User\NotificationsService;
use App\Services\User\ProfileService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

#[Group('Пользователь', weight: 200)]
final class NotificationController extends Controller
{
    public function __construct(
        protected NotificationsService $service
    )
    {
    }
    /**
     * Уведомления / Список уведомлений
     */
    public function notifications(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->service->getNotifications($request->user());
        return NotificationsResource::collection($notifications);
    }

    /**
     * Уведомления / Количество непрочитанных уведомлений
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->service->getUnreadCount($request->user());
        return response()->json(['data' => ['unread_count' => $count]]);
    }

}
