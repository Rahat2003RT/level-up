<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Checklist\StoreDailyChecklistRequest;
use App\Http\Requests\User\Contact\GetContactsRequest;
use App\Http\Requests\User\Contact\StoreContactRequest;
use App\Http\Requests\User\Contact\UpdateContactRequest;
use App\Http\Requests\User\Goal\StoreUserGoalRequest;
use App\Http\Requests\User\Player\ShowChecklistRequest;
use App\Http\Requests\User\Player\StatisticsRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\DailyChecklistResource;
use App\Http\Resources\PlayerStatisticsResource;
use App\Http\Resources\TeamPlanResource;
use App\Http\Resources\UserResource;
use App\Models\Contact;
use App\Services\User\LeaderService;
use App\Services\User\PlayerService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Пользователь / Player', weight: 250)]
final class PlayerController extends Controller
{
    public function __construct(
        protected PlayerService $service
    )
    {

    }

    /**
     * Прогресс пользователя
     * @param Request $request
     * @return JsonResponse
     */
    public function progress(Request $request): JsonResponse
    {
        $progress = $this->service->getProgress($request->user());

        return response()->json([
            'progress' => $progress
        ]);
    }
    /**
     * Просмотр чек-листа за выбранный день.
     * @param ShowChecklistRequest $request
     * @return DailyChecklistResource
     */
    public function showChecklist(ShowChecklistRequest $request): DailyChecklistResource
    {
        $result = $this->service->getOrCreateVirtual($request->user(), $request->validated());
        return DailyChecklistResource::make($result);
    }

    /**
     * Заполнение чек-листа за сегодня.
     * @param StoreDailyChecklistRequest $request
     * @return DailyChecklistResource
     * @throws AuthorizationException
     */
    public function storeChecklist(StoreDailyChecklistRequest $request): DailyChecklistResource
    {
        $checklist = $this->service->storeAndCompleteToday($request->user(), $request->validated());
        return DailyChecklistResource::make($checklist);
    }

    /**
     * Установить для сегодняшнего дня статус "Выходной".
     * @param Request $request
     * @return DailyChecklistResource
     * @throws AuthorizationException
     */
    public function setDayOff(Request $request): DailyChecklistResource
    {
        $checklist = $this->service->setDayOffToday($request->user());
        return DailyChecklistResource::make($checklist);
    }

    /**
     * Получить статистику игрока за выбранный период.
     * @param StatisticsRequest $request
     * @return PlayerStatisticsResource
     */
    public function statistics(StatisticsRequest $request): PlayerStatisticsResource
    {
        $stats = $this->service->getStatistics($request->user(), $request->validated());
        return PlayerStatisticsResource::make($stats);
    }

    /**
     * Список контактов
     * @param GetContactsRequest $request
     * @return AnonymousResourceCollection
     */
    public function contacts(GetContactsRequest $request): AnonymousResourceCollection
    {
        $result = $this->service->getContactsByType($request->user(), $request->validated());
        return ContactResource::collection($result['contacts'])
            ->additional(['total_volume' => $result['total_volume']]);
    }

    /**
     * Создать новый контакт.
     * @param StoreContactRequest $request
     * @return ContactResource
     */
    public function storeContact(StoreContactRequest $request): ContactResource
    {
        $contact = $this->service->createContact($request->user(), $request->validated());
        return ContactResource::make($contact);
    }

    /**
     * Редактировать контакт
     * @param UpdateContactRequest $request
     * @param Contact $contact
     * @return ContactResource
     */
    public function updateContact(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $updatedContact = $this->service->updateContact($contact, $request->validated());
        return ContactResource::make($updatedContact);
    }

    /**
     * Удалить контакт
     * @param Request $request
     * @param Contact $contact
     * @return Response
     * @throws AuthorizationException
     */
    public function destroyContact(Request $request, Contact $contact): Response
    {
        if ($contact->user_id !== $request->user()->id) {throw new AuthorizationException('You do not own this contact.');}
        $this->service->deleteContact($contact);
        return response()->noContent();
    }


    /**
     * Принять или отклонить приглашение
     * @param Request $request
     * @param string $token
     * @return JsonResponse
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
     * Получить данные о команде
     */
    public function getTeamByToken(Request $request, string $token): JsonResponse
    {
        $data = $this->service->getTeamDataByToken($request->user(), $token);
        return response()->json($data);
    }


    /**
     * Получить командный план, установленный лидером.
     */
    public function getTeamPlan(Request $request, LeaderService $leaderService): TeamPlanResource
    {
        $plan = $leaderService->getTeamPlan($request->user());
        return TeamPlanResource::make($plan);
    }

    /**
     * Выйти из текущей команды.
     */
    public function leaveTeam(Request $request): Response
    {
        $this->service->leaveTeam($request->user());
        return response()->noContent();
    }
}
