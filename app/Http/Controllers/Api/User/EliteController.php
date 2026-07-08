<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Elite\GetTeamLeadersRequest;
use App\Services\User\EliteService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Group('Пользователь / Elite', weight: 270)]
final class EliteController extends Controller
{
    public function __construct(
        protected EliteService $service
    ) {}

    /**
     * Генерация ссылки приглашения Лидеров в команду
     */
    public function generateInviteLink(Request $request): JsonResponse
    {
        $link = $this->service->generateInvitation($request->user());
        return response()->json(['data' => ['invite_url' => $link]]);
    }

    /**
     * Список лидеров
     */
    /**
     * Получить список лидеров команды Элиты с пагинацией.
     */
    public function teamMembers(GetTeamLeadersRequest $request): JsonResponse
    {
        $paginator = $this->service->getTeamLeaders($request->user(), $request->validated());

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        ]);
    }
}
