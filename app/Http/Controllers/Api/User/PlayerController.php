<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Checklist\StoreDailyChecklistRequest;
use App\Http\Requests\User\Player\StatisticsRequest;
use App\Http\Resources\DailyChecklistResource;
use App\Http\Resources\PlayerStatisticsResource;
use App\Models\DailyChecklist;
use App\Services\User\PlayerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlayerController extends Controller
{
    protected function __construct(
        protected PlayerService $service
    )
    {

    }
    /**
     * Просмотр чеклиста за выбранный день.
     * @param Request $request
     * @return DailyChecklistResource|JsonResponse|Response
     */
    public function show(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        $result = $this->service->getOrCreateVirtual(
            $request->user(),
            $date
        );
        if (!$result) {
            abort(404, 'Checklist not found for the specified date.');
        }
        return DailyChecklistResource::make($result);
    }

    /**
     * Создать или обновить чек-лист за сегодня.
     *
     * @param StoreDailyChecklistRequest $request
     * @return DailyChecklistResource
     */
    public function storeOrUpdate(StoreDailyChecklistRequest $request): DailyChecklistResource
    {
        $checklist = $this->service->storeOrUpdateToday(
            $request->user(),
            $request->validated()
        );

        return DailyChecklistResource::make($checklist);
    }

    /**
     * Отметить сегодняшний чек-лист как выполненный.
     *
     * @param Request $request
     * @return DailyChecklistResource
     */
    public function complete(Request $request): DailyChecklistResource
    {
        $checklist = $this->service->completeToday($request->user());

        return DailyChecklistResource::make($checklist);
    }

    /**
     * Установить для сегодняшнего дня статус "Выходной".
     *
     * @param Request $request
     * @return DailyChecklistResource
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
}
