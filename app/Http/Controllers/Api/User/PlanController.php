<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Checklist\StoreRequest;
use App\Http\Requests\User\Player\ShowChecklistRequest;
use App\Http\Requests\User\Statistics\IndexRequest;
use App\Http\Resources\DailyChecklistResource;
use App\Http\Resources\LeadershipChecklistResource;
use App\Http\Resources\ProgressResource;
use App\Services\User\PlanService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('90 дней план', weight: 200)]
final class PlanController extends Controller
{
    public function __construct(
        protected PlanService $service
    )
    {

    }

    /**
     * Прогресс
     * @param Request $request
     * @return ProgressResource
     */
    public function progress(Request $request): ProgressResource
    {
        $progress = $this->service->getProgress($request->user());
        return ProgressResource::make($progress);
    }

    /**
     * Статистика
     * @param IndexRequest $request
     * @return JsonResponse
     */
    public function statistics(IndexRequest $request)
    {
        $stats = $this->service->getStatistics($request->user(), $request->validated());
        return response()->json(['data' => $stats]);
    }

    /**
     * Чек-лист / Просмотр чек-листа за выбранный день.
     * @param ShowChecklistRequest $request
     * @return DailyChecklistResource|LeadershipChecklistResource
     */
    public function checklist(ShowChecklistRequest $request)
    {
        $user = $request->user();
        $result = $this->service->getChecklist($user, $request->validated());

        $data = is_array($result) ? (object) $result : $result;

        return $user->can('access-leader')
            ? LeadershipChecklistResource::make($data)
            : DailyChecklistResource::make($data);
    }

    /**
     * Чек-лист / Заполнение чек-листа за сегодня.
     */
    public function storeChecklist(StoreRequest $request)
    {
        $user = $request->user();
        $checklist = $this->service->storeChecklist($user, $request->validated());

        return $user->can('access-leader')
            ? LeadershipChecklistResource::make($checklist)
            : DailyChecklistResource::make($checklist);
    }

    /**
     * Чек-лист / Выбор или удаление выходного дня по дате.
     */
    public function toggleDayOff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        try {
            $result = $this->service->toggleDayOff($request->user(), $validated['date']);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Чек-лист / Получение всех выходных дней пользователя.
     */
    public function getDaysOff(Request $request): JsonResponse
    {
        $dates = $this->service->getDaysOff($request->user());
        return response()->json(['data' => $dates]);
    }
}
