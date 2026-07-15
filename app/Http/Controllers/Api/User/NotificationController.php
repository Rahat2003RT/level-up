<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationsResource;
use App\Services\User\NotificationsService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function notifications(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->service->getNotifications($request->user());
        return NotificationsResource::collection($notifications);
    }

    /**
     * Уведомления / Количество непрочитанных уведомлений
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->service->getUnreadCount($request->user());
        return response()->json(['data' => ['unread_count' => $count]]);
    }

}
