<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Player\StatisticsRequest;
use App\Http\Resources\LeaderTeamStatisticsResource;
use App\Services\User\LeaderService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Пользователь / Leader', weight: 260)]
final class LeaderController extends Controller
{
    public function __construct(
        protected LeaderService $service
    )
    {
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
