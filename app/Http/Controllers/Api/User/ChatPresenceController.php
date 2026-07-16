<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatPresenceRequest;
use App\Services\ChatPresenceService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Чаты / Присутствие', weight: 290)]
final class ChatPresenceController extends Controller
{
    public function __construct(
        private readonly ChatPresenceService $presenceService
    ) {}

    /**
     * Пинг
     */
    public function ping(int $chatId, Request $request): Response
    {
        $userId = $request->user()->id;

        $this->presenceService->ping($chatId, $userId);

        return response()->noContent();
    }

    /**
     * Выход из чата
     */
    public function leave(int $chatId, Request $request): Response
    {
        $this->presenceService->leave($chatId, $request->user()->id);
        return response()->noContent();
    }
}
