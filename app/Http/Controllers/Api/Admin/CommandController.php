<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Command\AddMemberRequest;
use App\Http\Requests\Admin\Command\IndexRequest;
use App\Http\Requests\Admin\Command\SearchAvailableRequest;
use App\Models\User;
use App\Services\Admin\CommandService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

#[Group('Команды / Админка', weight: 50)]
final class CommandController extends Controller
{
    public function __construct(
        protected CommandService $service
    )
    {
    }

    /**
     * Список
     */
    public function index(IndexRequest $request): JsonResponse
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
     * О команде
     */
    public function show(User $user): JsonResponse
    {
        $data = $this->service->getCommandDetails($user);
        return response()->json(['data' => $data]);
    }

    /**
     * Добавление пользователя
     */
    public function addMember(AddMemberRequest $request, User $user): Response
    {
        /** @var User $member */
        $member = User::findOrFail($request->validated()['member_id']);
        $this->service->addMember($user, $member);
        return response()->noContent();
    }

    /**
     * Изгнание пользователя
     */
    public function removeMember(User $member): Response
    {
        $this->service->removeMember($member);
        return response()->noContent();
    }


    /**
     * Поиск свободных пользователей
     */
    public function searchAvailable(SearchAvailableRequest $request, User $user): JsonResponse
    {
        $paginator = $this->service->searchAvailableUsers($user, $request->validated());

        return response()->json([
            'data' => collect($paginator->items())->map(fn($u) => [
                'id'     => $u->id,
                'name'   => $u->name . ' ' . $u->surname,
                'avatar' => $u->avatar_path ?? null,
                'role'   => $u->role?->value,
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ]
        ]);
    }
}
