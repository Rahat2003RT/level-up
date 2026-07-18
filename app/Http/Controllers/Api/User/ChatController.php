<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\GetChatsRequest;
use App\Http\Resources\ChatResource;
use App\Models\Chat;
use App\Services\User\ChatService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Чаты', weight: 280)]
final class ChatController extends Controller
{
    public function __construct(
        protected ChatService $service
    ) {
    }

    /**
     * Список чатов
     * @param GetChatsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(GetChatsRequest $request): AnonymousResourceCollection
    {
        $chats = $this->service->getChatsForUser($request->user(), $request->validated());
        return ChatResource::collection($chats);
    }

    /**
     * Получить детальную информацию о конкретном чате
     * @param Chat $chat
     * @param Request $request
     * @return ChatResource
     */
    public function show(Chat $chat, Request $request): ChatResource
    {
        $chatData = $this->service->showChat($chat, $request->user());

        return ChatResource::make($chatData);
    }
}
