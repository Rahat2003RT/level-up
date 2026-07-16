<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProgressResource;
use App\Services\User\PlanService;
use Dedoc\Scramble\Attributes\Group;
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
     * @param Request $request
     * @return StatisticsResource
     */
    public function statistics(Request $request): StatisticsResource
    {
        $statistics = $this->service->getStatisctics($request->user());
        return StatisticsResource::make($statistics);
    }

    public function checklist(Request $request)
    {

    }

    public function storeChecklist(Request $request)
    {

    }

    public function setDayOff(Request $request)
    {

    }
}
