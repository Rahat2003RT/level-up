<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatAccessRequest;
use App\Http\Requests\User\Chat\StoreMessageRequest;
use App\Http\Requests\User\Chat\UpdateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Services\User\MessageService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Сообщения', weight: 290)]
final class MessageController extends Controller
{
    public function __construct(
        protected MessageService $service
    )
    {
    }

    /**
     * Список сообщений чата
     */
    public function index(ChatAccessRequest $request, Chat $chat): AnonymousResourceCollection
    {
        $messages = $this->service->getMessages($chat, $request->validated());
        $this->service->markAsRead($chat, $request->user());
        $type = match (true) {
            $request->filled('around_id') => 'jump',
            $request->filled('after_id') => 'sync',
            default => 'history'
        };
        return MessageResource::collection($messages)->additional([
            'meta' => ['type' => $type]
        ]);
    }

    public function store(StoreMessageRequest $request, Chat $chat): MessageResource
    {
        $message = $this->service->storeMessage($chat, $request->user(), $request->validated());
        return MessageResource::make($message);
    }

    public function update(UpdateMessageRequest $request, Chat $chat, Message $message): MessageResource
    {
        $message = $this->service->updateMessage($message, $request->validated());
        return MessageResource::make($message);
    }

    public function read(Chat $chat): Response
    {
        $this->service->markAsRead($chat, request()->user());
        return response()->noContent();
    }

    /**
     * Удаление сообщения
     * @param Chat $chat
     * @param Message $message
     * @return Response
     */
    public function destroy(Chat $chat, Message $message): Response
    {
        abort_if($message->sender_id !== request()->user()->id, 403);
        $this->service->deleteMessage($message);
        return response()->noContent();
    }
}
