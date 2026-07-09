<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leader\Checklist\StoreLeadershipChecklistRequest;
use App\Http\Requests\Leader\Contact\StoreLeaderContactRequest;
use App\Http\Requests\Leader\Contact\UpdateLeaderContactRequest;
use App\Http\Requests\User\Contact\GetContactsRequest;
use App\Http\Requests\User\Player\ShowChecklistRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\LeadershipChecklistResource;
use App\Models\Contact;
use App\Services\User\LeaderService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Пользователь / Leader', weight: 260)]
final class LeaderController extends Controller
{
    public function __construct(
        protected LeaderService $service
    )
    {
    }

    /**
     * Список контактов
     * @param GetContactsRequest $request
     * @return AnonymousResourceCollection
     */
    public function contacts(GetContactsRequest $request): AnonymousResourceCollection
    {
        $result = $this->service->getContacts($request->user(), $request->validated());
        return ContactResource::collection($result['contacts'])->additional(['total_volume' => $result['total_volume']]);
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
     * @return Response
     * @throws AuthorizationException
     */
    public function destroyContact(Request $request, Contact $contact): Response
    {
        if ($contact->user_id !== $request->user()->id) {
            throw new AuthorizationException('You do not own this contact.');
        }
        $this->service->deleteContact($contact);
        return response()->noContent();
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
     * Статистика для главной
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboardStatistics(Request $request): JsonResponse
    {
        $stats = $this->service->getDashboardStatistics($request->user());
        return response()->json(['data' => $stats]);
    }
}
