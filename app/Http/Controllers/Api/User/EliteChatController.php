<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Elite\Chat\GetChatsRequest;
use App\Http\Requests\Elite\Chat\StoreMessageRequest;
use App\Http\Requests\Elite\Chat\UpdateMessageRequest;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Services\User\EliteChatService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Пользователь / Elite Chats', weight: 280)]
final class EliteChatController extends Controller
{
    public function __construct(
        protected EliteChatService $service
    ) {
    }

    /**
     * Чаты / Список чатов Elite
     *
     * Получить список всех чатов с лидерами. Доступен поиск по имени лидера.
     */
    public function index(GetChatsRequest $request): AnonymousResourceCollection
    {
        $chats = $this->service->getChats($request->user(), $request->validated());
        return ChatResource::collection($chats);
    }

    /**
     * Чаты / Сообщения чата
     */
    public function show(Request $request, Chat $chat): AnonymousResourceCollection
    {
        $limit = (int) $request->query('limit', 30);
        $messages = $this->service->getMessages($request->user(), $chat, $limit);

        return MessageResource::collection($messages);
    }

    /**
     * Чаты / Отправить сообщение
     */
    public function sendMessage(StoreMessageRequest $request, Chat $chat): MessageResource
    {
        $message = $this->service->createMessage($request->user(), $chat, $request->validated());

        return MessageResource::make($message);
    }

    /**
     * Чаты / Редактировать сообщение
     */
    public function updateMessage(UpdateMessageRequest $request, Message $message): MessageResource
    {
        $updatedMessage = $this->service->updateMessage($request->user(), $message, $request->validated());

        return MessageResource::make($updatedMessage);
    }

    /**
     * Чаты / Удалить сообщение
     */
    public function destroyMessage(Request $request, Message $message): Response
    {
        $this->service->deleteMessage($request->user(), $message);

        return response()->noContent();
    }
}
