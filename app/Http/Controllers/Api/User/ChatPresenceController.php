<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Services\ChatPresenceService;
use App\Services\User\MessageService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Чаты', weight: 290)]
final class ChatPresenceController extends Controller
{
    public function __construct(
        private readonly ChatPresenceService $presenceService,
        private readonly MessageService      $messageService
    )
    {
    }

    /**
     * Пинг
     * @param Chat $chat
     * @param Request $request
     * @return Response
     */
    public function ping(Chat $chat, Request $request): Response
    {
        $this->presenceService->ping($chat, $request->user());
        $this->messageService->markAsRead($chat, $request->user());
        return response()->noContent();
    }

    /**
     * Выход из чата
     * @param Chat $chat
     * @param Request $request
     * @return Response
     */
    public function leave(Chat $chat, Request $request): Response
    {
        $this->presenceService->leave($chat, $request->user());
        return response()->noContent();
    }
}
