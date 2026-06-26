<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Checklist\StoreDailyChecklistRequest;
use App\Http\Requests\User\Goal\StoreUserGoalRequest;
use App\Http\Requests\User\Player\ShowChecklistRequest;
use App\Http\Requests\User\Player\StatisticsRequest;
use App\Http\Resources\DailyChecklistResource;
use App\Http\Resources\PlayerStatisticsResource;
use App\Services\User\PlayerService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Просмотр чек-листа за выбранный день.
     * @param ShowChecklistRequest $request
     * @return DailyChecklistResource
     */
    public function show(ShowChecklistRequest $request): DailyChecklistResource
    {
        $result = $this->service->getOrCreateVirtual($request->user(), $request->validated());
        return DailyChecklistResource::make($result);
    }

    /**
     * Создать или обновить чек-лист за сегодня.
     * @param StoreDailyChecklistRequest $request
     * @return DailyChecklistResource
     * @throws AuthorizationException
     */
    public function storeOrUpdate(StoreDailyChecklistRequest $request): DailyChecklistResource
    {
        $checklist = $this->service->storeAndCompleteToday($request->user(), $request->validated());
        return DailyChecklistResource::make($checklist);
    }

    /**
     * Установить для сегодняшнего дня статус "Выходной".
     *
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
     *
     * @param StatisticsRequest $request
     * @return PlayerStatisticsResource
     */
    public function statistics(StatisticsRequest $request): PlayerStatisticsResource
    {
        $stats = $this->service->getStatistics($request->user(), $request->validated());
        return PlayerStatisticsResource::make($stats);
    }

    /**
     * Сохранить или обновить цели игрока.
     *
     * @param StoreUserGoalRequest $request
     * @return Response
     */
    public function storeGoal(StoreUserGoalRequest $request): Response
    {
        $this->service->updateOrCreateGoal($request->user(), $request->validated());
        return response()->noContent();
    }
}
