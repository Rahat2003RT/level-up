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

        // 1. Получаем ID всех лидеров, привязанных к этому Elite
        // (Предполагаем связь $elite->leaders() или по полю leader_id / parent_id.
        // Если у тебя связь называется иначе, например, через роли — адаптируй под свой проект)
        $leaderIds = $elite->leaders()->pluck('id')->toArray();
        $totalLeaders = count($leaderIds);

        if ($totalLeaders === 0) {
            return [
                'total_leaders' => 0,
                'active_leaders' => 0,
                'total_team_volume' => 0.0,
            ];
        }

        // 2. Считаем активных лидеров за сегодня
        // Активным считается лидер, у которого есть заполненный чек-лист за сегодня (выполнен или выходной)
        $activeLeadersCount = LeadershipChecklist::whereIn('user_id', $leaderIds)
            ->where('date', $todayStr)
            ->where(function ($query) {
                $query->where('is_completed', true)
                    ->orWhere('is_day_off', true);
            })
            ->count();

        // 3. Считаем общий объем (volume) всей структуры
        // Сюда входят контакты самих лидеров + контакты всех их игроков
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
