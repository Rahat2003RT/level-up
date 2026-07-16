<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leader\Checklist\StoreLeadershipChecklistRequest;
use App\Http\Requests\User\Player\ShowChecklistRequest;
use App\Http\Requests\User\Player\StatisticsRequest;
use App\Http\Resources\LeadershipChecklistResource;
use App\Http\Resources\LeaderTeamStatisticsResource;
use App\Services\User\LeaderService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

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
