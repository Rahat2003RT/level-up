<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leader\Team\UpdateTeamPlanRequest;
use App\Http\Requests\User\Team\AnswerInvitationRequest;
use App\Http\Resources\TeamPlanResource;
use App\Models\User;
use App\Services\User\TeamService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

#[Group('Команда', weight: 310)]
final class TeamController extends Controller
{
    public function __construct(
        protected TeamService $service,
    )
    {

    }

    /**
     * Приглашения / Генерация ссылки приглашения
     * @param Request $request
     * @return JsonResponse
     */
    public function generateInviteLink(Request $request): JsonResponse
    {
        $link = $this->service->generateInviteLink($request->user());
        return response()->json(['data' => ['invite_url' => $link]]);
    }

    /**
     * Приглашения / Принять или отклонить
     * @param AnswerInvitationRequest $request
     * @param string $token
     * @return JsonResponse
     * @throws ValidationException
     */
    public function answerInvitation(AnswerInvitationRequest $request, string $token): JsonResponse
    {
        $result = $this->service->handleInvitation(
            $request->user(),
            $token,
            (bool)$request->validated()['accept']
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Приглашения / Информация о команде
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getTeamByToken(Request $request, string $token): JsonResponse
    {
        $data = $this->service->getTeamDataByToken($request->user(), $token);
        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Команда / Получение членов команды текущего пользователя
     * @param Request $request
     * @return JsonResponse
     */
    public function getMembers(Request $request): JsonResponse
    {
        $paginator = $this->service->getTeamMembers($request->user(), $request->all());

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        ]);
    }

    /**
     * Команда / Получить план
     */
    public function getTeamPlan(Request $request): TeamPlanResource
    {
        $plan = $this->service->getTeamPlan($request->user());
        return TeamPlanResource::make($plan);
    }

    /**
     * Команда / Установить план
     * @param UpdateTeamPlanRequest $request
     * @return TeamPlanResource
     */
    public function updateTeamPlan(UpdateTeamPlanRequest $request): TeamPlanResource
    {
        $plan = $this->service->updateTeamPlan($request->user(), $request->validated());
        return TeamPlanResource::make($plan);
    }

    /**
     * Команда / Выйти из текущей команды
     * @param Request $request
     * @return Response
     * @throws ValidationException
     */
    public function leaveTeam(Request $request): Response
    {
        $this->service->leaveCurrentTeam($request->user());
        return response()->noContent();
    }
    /**
     * Команда / Удалить пользователя из команды
     * @param Request $request
     * @param User $member
     * @return Response
     * @throws ValidationException
     */
    public function kickMember(Request $request, User $member): Response
    {
        $this->service->removeMemberFromTeam($request->user(), $member);
        return response()->noContent();
    }
}
