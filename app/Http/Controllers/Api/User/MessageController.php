<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channels\Chat\Message\UpdateMessageRequest;
use App\Http\Requests\Chat\ChatAccessRequest;
use App\Http\Requests\User\Chat\StoreMessageRequest;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Services\User\MessageService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Сообщения', weight: 290)]
final class MessageController extends Controller
{
    public function __construct(
        protected MessageService $service
    ) {
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
            'meta' => [
                'type' => $type
            ]
        ]);
    }

    public function store(StoreMessageRequest $request, Chat $chat): ChatResource
    {
        $message = $this->service->storeMessage($chat, $request->user(), $request->validated());
        return ChatResource::make($message);
    }

    public function update(UpdateMessageRequest $request, Message $message): ChatResource
    {
        $message = $this->service->updateMessage($message, $request->validated());
        return ChatResource::make($message);
    }

}
