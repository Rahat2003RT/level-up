<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\User\EliteService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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


}
