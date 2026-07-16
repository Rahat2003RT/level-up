<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Contact\IndexRequest;
use App\Http\Requests\User\Contact\StoreRequest;
use App\Http\Requests\User\Contact\UpdateRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Services\User\ContactService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ContactController extends Controller
{
    public function __construct(
        protected ContactService $service
    )
    {
    }

    /**
     * Контакты / Список контактов
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        $result = $this->service->getContacts($request->user(), $request->validated());
        return ContactResource::collection($result['contacts'])->additional([
            'filtered_volume' => $result['filtered_volume'],
            'total_volume' => $result['total_volume'],
        ]);
    }

    /**
     * Контакты / Создать новый контакт
     */
    public function store(StoreRequest $request): ContactResource
    {
        $contact = $this->service->createContact($request->user(), $request->validated());
        return ContactResource::make($contact);
    }

    /**
     * Контакты / Редактировать контакт
     */
    public function update(UpdateRequest $request, Contact $contact): ContactResource
    {
        $updatedContact = $this->service->updateContact($contact, $request->validated());
        return ContactResource::make($updatedContact);
    }

    /**
     * Контакты / Удалить контакт
     */
    public function destroy(Request $request, Contact $contact): Response
    {
        $this->service->deleteContact($contact, $request->user());
        return response()->noContent();
    }
}
