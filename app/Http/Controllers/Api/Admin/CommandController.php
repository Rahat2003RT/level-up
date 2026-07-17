<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Command\AddMemberRequest;
use App\Http\Requests\Admin\Command\SearchAvailableRequest;
use App\Models\User;
use App\Services\Admin\CommandService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Админ / Управление командами', weight: 50)]
class CommandController extends Controller
{
    public function __construct(
    protected CommandService $service
) {}

    /**
     * Список команд
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->getCommandsList($request->all());

        return response()->json([
            'data' => collect($paginator->items())->map(fn($u) => [
                'id'            => $u->id,
                'name'          => $u->name . ' ' . $u->surname,
                'role'          => $u->role?->value,
                'members_count' => $u->members_count,
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ]
        ]);
    }

    /**
     * Данные команды и список её участников
     */
    public function show(User $user): JsonResponse
    {
        $data = $this->service->getCommandDetails($user);
        return response()->json(['data' => $data]);
    }

    /**
     * Удалить пользователя из команды
     */
    public function removeMember(User $member): Response
    {
        $this->service->removeMember($member);
        return response()->noContent();
    }

    /**
     * Добавить пользователя в команду
     */
    public function addMember(AddMemberRequest $request, User $user): Response
    {
        $member = User::findOrFail($request->validated()['member_id']);

        $this->service->addMember($user, $member);

        return response()->noContent();
    }

    /**
     * Поиск доступных для добавления пользователей
     */
    public function searchAvailable(SearchAvailableRequest $request, User $user): JsonResponse
    {
        $paginator = $this->service->searchAvailableUsers($user, $request->validated());

        return response()->json([
            'data' => collect($paginator->items())->map(fn($u) => [
                'id'      => $u->id,
                'name'    => $u->name . ' ' . $u->surname,
                'avatar'  => $u->avatar_path ?? null,
                'role'    => $u->role?->value,
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ]
        ]);
    }
}
