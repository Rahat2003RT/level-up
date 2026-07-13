<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\EliteStatisticsResource;
use App\Services\User\EliteService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Пользователь / Elite', weight: 270)]
final class EliteController extends Controller
{
    public function __construct(
        protected EliteService $service
    ) {
    }

    /**
     * Статистика / Общая статистика Elite
     *
     * Возвращает общее количество лидеров, количество активных лидеров за сегодня
     * и суммарный объем (volume) всей команды.
     *
     * @param Request $request
     * @return EliteStatisticsResource
     */
    public function statistics(Request $request): EliteStatisticsResource
    {
        $stats = $this->service->getStatistics($request->user());

        return EliteStatisticsResource::make($stats);
    }
}
