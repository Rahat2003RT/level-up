<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\User\LeaderService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Управление Командой / Leader / В разработке', weight: 260)]
final class LeaderController extends Controller
{
    public function __construct(
        protected LeaderService $service
    ) {}

    /**
     * Генерация ссылки приглашения
     */
    public function generateInviteLink(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'leader') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $link = $this->service->generateInvitation($request->user());

        return response()->json(['invite_url' => $link]);
    }

    /**
     * 3. Получить данные о команде
     */
    public function getTeamByToken(Request $request, string $token): JsonResponse
    {
        $data = $this->service->getTeamDataByToken($request->user(), $token);
        return response()->json($data);
    }

    /**
     * 4. Принять или отклонить приглашение
     */
    public function answerInvitation(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'accept' => 'required|boolean'
        ]);

        $result = $this->service->handleInvitation(
            $request->user(),
            $token,
            (bool)$request->input('accept')
        );

        return response()->json($result);
    }

    /**
     * 5. Получить список участников команды
     */
    public function teamMembers(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'leader') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $filters = $request->validate([
            'query' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $members = $this->service->getTeamMembers($request->user(), $filters);

        return response()->json($members);
    }

    /**
     * 6. Удалить пользователя из команды
     */
    public function kickPlayer(Request $request, User $player): JsonResponse
    {
        if ($request->user()->role !== 'leader') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $this->service->removePlayerFromTeam($request->user(), $player);

        return response()->json(['message' => 'Игрок успешно удален из команды.']);
    }
}
