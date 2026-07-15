<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Elite\Chat\GetChatsRequest;
use App\Http\Resources\ChatResource;
use App\Services\User\ChatService;
use Dedoc\Scramble\Attributes\Group;
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
     */
    public function index(GetChatsRequest $request): AnonymousResourceCollection
    {
        $result = $this->service->getChatsForUser($request->user(), $request->validated());
        return ChatResource::collection($result);
    }
}
