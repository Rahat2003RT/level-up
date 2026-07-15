<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Contact;
use App\Models\LeadershipChecklist;
use App\Models\User;
use Carbon\Carbon;

final class EliteService
{
    /**
     * Получить общую статистику для Elite-пользователя.
     *
     * @param User $elite
     * @return array
     */
    public function getStatistics(User $elite): array
    {
        $todayStr = Carbon::today()->toDateString();

        // 1. Получаем ID всех лидеров, привязанных к этому Elite (через отношение players)
        $leaderIds = $elite->players()->pluck('id')->toArray();
        $totalLeaders = count($leaderIds);

        if ($totalLeaders === 0) {
            return [
                'total_leaders' => 0,
                'active_leaders' => 0,
                'total_team_volume' => 0.0,
            ];
        }

        // 2. Считаем активных лидеров за сегодня
        $activeLeadersCount = LeadershipChecklist::whereIn('user_id', $leaderIds)
            ->where('date', $todayStr)
            ->where(function ($query) {
                $query->where('is_completed', true)
                    ->orWhere('is_day_off', true);
            })
            ->count();

        // 3. Считаем общий объем (volume) всей структуры (лидеры + их игроки)
        $playerIds = User::whereIn('leader_id', $leaderIds)->pluck('id')->toArray();
        $allTeamUserIds = array_merge($leaderIds, $playerIds);

        $totalTeamVolume = (float) Contact::whereIn('user_id', $allTeamUserIds)->sum('volume');

        return [
            'total_leaders' => $totalLeaders,
            'active_leaders' => $activeLeadersCount,
            'total_team_volume' => $totalTeamVolume,
        ];
    }
}
