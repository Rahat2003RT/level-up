<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leader\Checklist\StoreLeadershipChecklistRequest;
use App\Http\Requests\Leader\Contact\StoreLeaderContactRequest;
use App\Http\Requests\Leader\Contact\UpdateLeaderContactRequest;
use App\Http\Requests\Leader\Team\GetTeamMembersRequest;
use App\Http\Requests\Leader\Team\UpdateTeamPlanRequest;
use App\Http\Requests\User\Contact\GetContactsRequest;
use App\Http\Requests\User\Player\ShowChecklistRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\LeadershipChecklistResource;
use App\Http\Resources\TeamPlanResource;
use App\Models\Contact;
use App\Models\User;
use App\Services\User\LeaderService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Пользователь / Leader', weight: 260)]
final class LeaderController extends Controller
{
    public function __construct(
        protected LeaderService $service
    )
    {
    }

    /**
     * Генерация ссылки приглашения
     */
    public function generateInviteLink(Request $request): JsonResponse
    {
        $link = $this->service->generateInvitation($request->user());
        return response()->json(['data' => ['invite_url' => $link]]);
    }


    /**
     * Получить список участников команды (без пагинации, с поиском по имени).
     *
     * @param GetTeamMembersRequest $request
     * @return JsonResponse
     */
    public function teamMembers(GetTeamMembersRequest $request): JsonResponse
    {
        $members = $this->service->getTeamMembers($request->user(), $request->validated());
        return response()->json(['data' => $members]);
    }

    /**
     * Удалить пользователя из команды
     */
    public function kickPlayer(Request $request, User $player): JsonResponse
    {
        if ($request->user()->role !== 'leader') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $this->service->removePlayerFromTeam($request->user(), $player);
        return response()->json(['message' => 'The player has been successfully removed from the team.']);
    }


    /**
     * Список контактов
     * @param GetContactsRequest $request
     * @return AnonymousResourceCollection
     */
    public function contacts(GetContactsRequest $request): AnonymousResourceCollection
    {
        $result = $this->service->getContacts($request->user(), $request->validated());
        return ContactResource::collection($result['contacts'])
            ->additional([
                'total_volume' => $result['total_volume']
            ]);
    }

    /**
     * Создать новый контакт.
     * @param StoreLeaderContactRequest $request
     * @return ContactResource
     */
    public function storeContact(StoreLeaderContactRequest $request): ContactResource
    {
        $contact = $this->service->createContact($request->user(), $request->validated());
        return ContactResource::make($contact);
    }

    /**
     * Редактировать контакт
     * @param UpdateLeaderContactRequest $request
     * @param Contact $contact
     * @return ContactResource
     */
    public function updateContact(UpdateLeaderContactRequest $request, Contact $contact): ContactResource
    {
        $updatedContact = $this->service->updateContact($contact, $request->validated());
        return ContactResource::make($updatedContact);
    }

    /**
     * Удалить контакт
     * @param Request $request
     * @param Contact $contact
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyContact(Request $request, Contact $contact): JsonResponse
    {
        if ($contact->user_id !== $request->user()->id) {
            throw new AuthorizationException('You do not own this contact.');
        }
        $this->service->deleteContact($contact);
        return response()->json(['message' => 'Contact deleted successfully.']);
    }


    /**
     * Просмотр чек-листа лидера за выбранный день.
     * @param ShowChecklistRequest $request
     * @return LeadershipChecklistResource
     */
    public function showChecklist(ShowChecklistRequest $request): LeadershipChecklistResource
    {
        $result = $this->service->getOrCreateVirtual($request->user(), $request->validated());
        return LeadershipChecklistResource::make(is_array($result) ? (object)$result : $result);
    }

    /**
     * Заполнение чек-листа лидера за сегодня.
     * @param StoreLeadershipChecklistRequest $request
     * @return LeadershipChecklistResource
     * @throws AuthorizationException
     */
    public function storeChecklist(StoreLeadershipChecklistRequest $request): LeadershipChecklistResource
    {
        $checklist = $this->service->storeAndCompleteToday($request->user(), $request->validated());
        return LeadershipChecklistResource::make($checklist);
    }

    /**
     * Установить для сегодняшнего дня лидера статус "Выходной".
     * @param Request $request
     * @return LeadershipChecklistResource
     * @throws AuthorizationException
     */
    public function setDayOff(Request $request): LeadershipChecklistResource
    {
        $checklist = $this->service->setDayOffToday($request->user());
        return LeadershipChecklistResource::make($checklist);
    }


    /**
     * Получить цели для команды.
     */
    public function getTeamPlan(Request $request): TeamPlanResource
    {
        $plan = $this->service->getTeamPlan($request->user());
        return TeamPlanResource::make($plan);
    }

    /**
     * Установить/обновить цели для команды.
     */
    public function updateTeamPlan(UpdateTeamPlanRequest $request): TeamPlanResource
    {
        $plan = $this->service->updateTeamPlan($request->user(), $request->validated());
        return TeamPlanResource::make($plan);
    }

    /**
     * Статистика для главной
     */
    public function dashboardStatistics(Request $request): JsonResponse
    {
        $stats = $this->service->getDashboardStatistics($request->user());
        return response()->json(['data' => $stats]);
    }
}
