<?php

namespace App\Services\User;

use App\Models\Contact;
use App\Models\DailyChecklist;
use App\Models\LeadershipChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

final class LeaderService
{

    /**
     * Получить командную статистику лидера за указанный период (в днях).
     *
     * @param User $leader
     * @param array $data
     * @return array
     */
    public function getTeamStatistics(User $leader, array $data): array
    {
        $days = $data['days'] ?? 30;
        $startDate = Carbon::today()->subDays($days - 1)->toDateString();
        $endDate = Carbon::today()->toDateString();

        $playerIds = $leader->players()->pluck('id')->toArray();
        $totalPlayers = count($playerIds);

        if ($totalPlayers === 0) {
            return [
                'period_days' => $days,
                'players_count' => 0,
                'active_players_count' => 0,
                'total_volume' => 0.0,
            ];
        }

        $playersWithMisses = DailyChecklist::whereIn('user_id', $playerIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_completed', false)
            ->where('is_day_off', false)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        $activePlayersCount = $totalPlayers - count($playersWithMisses);

        $totalVolume = (float) Contact::whereIn('user_id', $playerIds)->sum('volume');

        return [
            'period_days' => $days,
            'players_count' => $totalPlayers,
            'active_players_count' => $activePlayersCount,
            'total_volume' => $totalVolume,
        ];
    }
}
