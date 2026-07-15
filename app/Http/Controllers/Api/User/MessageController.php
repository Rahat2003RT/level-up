<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatAccessRequest;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Services\User\MessageService;
use Dedoc\Scramble\Attributes\Group;
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
}
