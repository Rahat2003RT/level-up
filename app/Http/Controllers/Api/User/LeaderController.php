<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Contact\GetContactsRequest;
use App\Http\Requests\User\Contact\StoreContactRequest;
use App\Http\Requests\User\Contact\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\User;
use App\Services\User\LeaderService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $link = $this->service->generateInvitation($request->user());
        return response()->json([
            'data' => [
                'invite_url' => $link
            ]
        ]);
    }





    /**
     * Получить список участников команды
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
     * Удалить пользователя из команды
     */
    public function kickPlayer(Request $request, User $player): JsonResponse
    {
        if ($request->user()->role !== 'leader') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $this->service->removePlayerFromTeam($request->user(), $player);

        return response()->json(['message' => 'Игрок успешно удален из команды.']);
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyContact(Request $request, Contact $contact): JsonResponse
    {
        if ($contact->user_id !== $request->user()->id) {
            throw new AuthorizationException('You do not own this contact.');
        }

        $this->service->deleteContact($contact);

        return response()->json([
            'message' => 'Contact deleted successfully.'
        ]);
    }
}
