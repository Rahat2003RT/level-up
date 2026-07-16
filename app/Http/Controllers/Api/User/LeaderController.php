<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leader\Checklist\StoreLeadershipChecklistRequest;
use App\Http\Requests\Leader\Contact\StoreLeaderContactRequest;
use App\Http\Requests\Leader\Contact\UpdateLeaderContactRequest;
use App\Http\Requests\User\Contact\GetContactsRequest;
use App\Http\Requests\User\Player\ShowChecklistRequest;
use App\Http\Requests\User\Player\StatisticsRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\LeadershipChecklistResource;
use App\Http\Resources\LeaderTeamStatisticsResource;
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
     * Чек-лист / Просмотр чек-листа за выбранный день.
     * @param ShowChecklistRequest $request
     * @return LeadershipChecklistResource
     */
    public function showChecklist(ShowChecklistRequest $request): LeadershipChecklistResource
    {
        $result = $this->service->getOrCreateVirtual($request->user(), $request->validated());
        return LeadershipChecklistResource::make(is_array($result) ? (object)$result : $result);
    }

    /**
     * Чек-лист / Заполнение чек-листа за сегодня.
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
     * Чек-лист / Установить для сегодняшнего дня статус "Выходной".
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
     * Статистика / Командная статистика за период
     * @param StatisticsRequest $request
     * @return LeaderTeamStatisticsResource
     */
    public function teamStatistics(StatisticsRequest $request): LeaderTeamStatisticsResource
    {
        $stats = $this->service->getTeamStatistics($request->user(), $request->validated());
        return LeaderTeamStatisticsResource::make($stats);
    }
}
