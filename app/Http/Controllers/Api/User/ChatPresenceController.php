<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatPresenceRequest;
use App\Models\Chat;
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
    public function ping(Chat $chat, Request $request): Response
    {
        $userId = $request->user()->id;

        $this->presenceService->ping($chat->id, $userId);

        return response()->noContent();
    }

    /**
     * Выход из чата
     */
    public function leave(Chat $chat, Request $request): Response
    {
        $this->presenceService->leave($chat->id, $request->user()->id);
        return response()->noContent();
    }
}
