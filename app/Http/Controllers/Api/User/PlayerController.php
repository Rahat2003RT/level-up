<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Checklist\StoreDailyChecklistRequest;
use App\Http\Requests\User\Player\ShowChecklistRequest;
use App\Http\Requests\User\Player\StatisticsRequest;
use App\Http\Resources\DailyChecklistResource;
use App\Http\Resources\PlayerStatisticsResource;
use App\Services\User\PlayerService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

#[Group('Пользователь / Player', weight: 250)]
final class PlayerController extends Controller
{
    public function __construct(
        protected PlayerService $service
    )
    {

    }

    /**
     * Статистика.
     * @param StatisticsRequest $request
     * @return PlayerStatisticsResource
     */
    public function statistics(StatisticsRequest $request): PlayerStatisticsResource
    {
        $stats = $this->service->getStatistics($request->user(), $request->validated());
        return PlayerStatisticsResource::make($stats);
    }
}
