<?php

namespace App\Services\User;

use App\Models\Contact;
use App\Models\DailyChecklist;
use App\Models\LeadershipChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

final class PlanService
{
    public function getProgress(User $user): array
    {
        if ($user->players()->exists()) {
            return $this->getLeaderProgress($user);
        }

        return $this->getUserProgress($user->id);
    }

    protected function getLeaderProgress(User $leader): array
    {
        $todayStr = Carbon::today()->toDateString();

        $leaderTodayChecklist = LeadershipChecklist::where('user_id', $leader->id)
            ->where('date', $todayStr)
            ->first();

        if ($leaderTodayChecklist) {
            $currentDayNumber = $leaderTodayChecklist->day_number;
        } else {
            $maxLeaderDay = LeadershipChecklist::where('user_id', $leader->id)->max('day_number') ?? 0;
            $currentDayNumber = $maxLeaderDay + 1;
        }
        $currentDayNumber = min(90, $currentDayNumber);
        $players = $leader->players()->with('checklists')->get();
        $totalPlayers = $players->count();

        $activePlayersCount = 0;
        $totalProgressPercentage = 0;

        foreach ($players as $player) {
            $playerChecklistsCount = $player->checklists->count();

            $playerTodayChecklist = $player->checklists->first(fn($c) => $c->date->toDateString() === $todayStr);
            if ($playerTodayChecklist) {
                $pDay = $playerTodayChecklist->day_number;
            } else {
                $pDay = $player->checklists->max('day_number') ?? 0;
            }

            $playerProgress = $pDay > 0 ? round(($pDay / 90) * 100) : 0;
            $totalProgressPercentage += min(100, $playerProgress);

            if ($playerChecklistsCount > 0 && $playerChecklistsCount >= $pDay) {
                $activePlayersCount++;
            }
        }

        $averageProgress = $totalPlayers > 0 ? round($totalProgressPercentage / $totalPlayers) : 0;

        return [
            'current_day_number' => $currentDayNumber,
            'plan_start_date' => $leader->created_at ? $leader->created_at->toDateString() : null,
            'players_count' => $totalPlayers,
            'active_count' => $activePlayersCount,
            'average_progress' => min(100, $averageProgress),
        ];
    }

    protected function getUserProgress(int $userId): array
    {
        $wins = DailyChecklist::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();
        $todayChecklist = $this->getTodayChecklist($userId);
        $currentDayNumber = $todayChecklist ? $todayChecklist->day_number : $this->getNextDayNumber($userId);

        $totalCourseDays = 90;
        $percentage = ($currentDayNumber / $totalCourseDays) * 100;
        $percentage = round($percentage);

        $planStartDate = Carbon::parse(
            DailyChecklist::where('user_id', $userId)->min('date') ?? Carbon::today()
        )->startOfDay()->toIso8601String();

        $loses = DailyChecklist::where('user_id', $userId)
            ->where('date', '<', Carbon::today()->toDateString())
            ->where('is_completed', false)
            ->where('is_day_off', false)
            ->count();

        $currentStreak = 0;
        $checklists = DailyChecklist::where('user_id', $userId)
            ->where('date', '<=', Carbon::today()->toDateString())
            ->orderBy('date', 'desc')
            ->get();

        /** @var Collection<DailyChecklist> $checklists */
        foreach ($checklists as $checklist) {
            if ($checklist->date == Carbon::today()->toDateString() && !$checklist->is_completed && !$checklist->is_day_off) {
                continue;
            }

            if ($checklist->is_completed || $checklist->is_day_off) {
                $currentStreak++;
            } else {
                break;
            }
        }

        return [
            'current_streak' => $currentStreak,
            'wins' => $wins,
            'misses' => $loses,
            'percentage' => min(100, max(0, $percentage)),
            'plan_start_date' => $planStartDate,
            'current_day_number' => $currentDayNumber,
        ];
    }

    protected function getTodayChecklist(int $userId): ?DailyChecklist
    {
        return DailyChecklist::where('user_id', $userId)
            ->where('date', Carbon::today()->toDateString())
            ->first();
    }

    protected function getNextDayNumber(int $userId): int
    {
        return DailyChecklist::where('user_id', $userId)->max('day_number') + 1;
    }















    public function getChecklist(User $user, array $data): array|object|null
    {
        $date = $data['date'];
        $today = Carbon::today()->toDateString();

        if ($user->can('access-leader')) {
            $checklist = LeadershipChecklist::where('user_id', $user->id)
                ->where('date', $date)
                ->first();

            if ($checklist) {
                $checklist->is_editable = $checklist->isEditable();
                return $checklist;
            }

            if ($date === $today) {
                $nextDayNumber = LeadershipChecklist::where('user_id', $user->id)->max('day_number') + 1;
                return [
                    'id' => null,
                    'user_id' => $user->id,
                    'date' => $date,
                    'day_number' => $nextDayNumber,
                    'is_completed' => false,
                    'is_day_off' => false,
                    'checked_team_activity' => false,
                    'contacted_players' => false,
                    'added_new_player' => false,
                    'held_online_meeting' => false,
                    'posted_engaged_social_media' => false,
                    'attracted_new_client' => false,
                    'brought_new_partner' => false,
                    'sent_new_invitations' => false,
                    'is_editable' => true,
                ];
            }
        } else {
            $checklist = DailyChecklist::where('user_id', $user->id)
                ->where('date', $date)
                ->first();

            if ($checklist) {
                $checklist->is_editable = $checklist->isEditable();
                return $checklist;
            }

            if ($date === $today) {
                $nextDayNumber = DailyChecklist::where('user_id', $user->id)->max('day_number') + 1;
                return [
                    'date' => $date,
                    'day_number' => $nextDayNumber,
                    'is_completed' => false,
                    'is_day_off' => false,
                    'scheduled_meetings' => 0,
                    'completed_meetings' => 0,
                    'new_clients' => 0,
                    'new_partners' => 0,
                    'business_conversations' => 0,
                    'presentations' => 0,
                    'sales' => 0,
                    'daily_income' => 0.0,
                    'social_media_activity' => false,
                    'communication_with_sponsor' => false,
                    'plans_for_the_day' => '',
                    'results_for_the_day' => '',
                    'notes_for_the_day' => '',
                    'is_editable' => true,
                ];
            }
        }

        return [];
    }

    /**
     * Создать/заполнить чек-лист на сегодня.
     */
    public function storeChecklist(User $user, array $data): mixed
    {
        $today = Carbon::today()->toDateString();

        if ($user->can('access-leader')) {
            $exists = LeadershipChecklist::where('user_id', $user->id)->whereDate('date', $today)->exists();
            if ($exists) {
                throw new AuthorizationException('The leadership checklist for today has already been completed.');
            }

            $dayNumber = LeadershipChecklist::where('user_id', $user->id)->max('day_number') + 1;

            return LeadershipChecklist::create(array_merge($data, [
                'user_id' => $user->id,
                'date' => $today,
                'day_number' => $dayNumber,
                'is_completed' => true,
                'is_day_off' => false,
            ]));
        } else {
            $exists = DailyChecklist::where('user_id', $user->id)->whereDate('date', $today)->exists();
            if ($exists) {
                throw new AuthorizationException('The checklist for today has already been completed and cannot be edited.');
            }

            $dayNumber = DailyChecklist::where('user_id', $user->id)->max('day_number') + 1;

            $checklist = DailyChecklist::create(array_merge($data, [
                'user_id' => $user->id,
                'date' => $today,
                'day_number' => $dayNumber,
                'is_completed' => true,
                'is_day_off' => false,
            ]));

            $checklist->progress = $this->getProgress($user);
            return $checklist;
        }
    }

    /**
     * Установить выходной день на сегодня.
     */
    public function setDayOff(User $user): mixed
    {
        $today = Carbon::today()->toDateString();

        if ($user->can('access-leader')) {
            $exists = LeadershipChecklist::where('user_id', $user->id)->where('date', $today)->exists();
            if ($exists) {
                throw new AuthorizationException("Today's leadership checklist has already been recorded.");
            }

            $dayNumber = LeadershipChecklist::where('user_id', $user->id)->max('day_number') + 1;

            return LeadershipChecklist::create([
                'user_id' => $user->id,
                'date' => $today,
                'day_number' => $dayNumber,
                'is_completed' => false,
                'is_day_off' => true,
                'checked_team_activity' => false,
                'contacted_players' => false,
                'added_new_player' => false,
                'held_online_meeting' => false,
                'posted_engaged_social_media' => false,
                'attracted_new_client' => false,
                'brought_new_partner' => false,
                'sent_new_invitations' => false,
            ]);
        } else {
            $exists = DailyChecklist::where('user_id', $user->id)->where('date', $today)->exists();
            if ($exists) {
                throw new AuthorizationException("Today's checklist has already been recorded.");
            }

            $dayNumber = DailyChecklist::where('user_id', $user->id)->max('day_number') + 1;

            $checklist = DailyChecklist::create([
                'user_id' => $user->id,
                'date' => $today,
                'day_number' => $dayNumber,
                'is_completed' => false,
                'is_day_off' => true,
                'scheduled_meetings' => 0,
                'completed_meetings' => 0,
                'new_clients' => 0,
                'new_partners' => 0,
                'business_conversations' => 0,
                'presentations' => 0,
                'sales' => 0,
                'daily_income' => 0,
                'social_media_activity' => false,
                'communication_with_sponsor' => false
            ]);

            $checklist->progress = $this->getProgress($user);
            return $checklist;
        }
    }





















    /**
     * Получить сводную статистику пользователя в зависимости от его ролей.
     */
    public function getStatistics(User $user, array $data): array
    {
        $stats = [];

        $stats['personal'] = $this->getPersonalStatistics($user, $data);
        if ($user->can('access-leader')) {
            $stats['team'] = $this->getTeamStatistics($user, $data);
        }
        if ($user->can('access-elite')) {
            $stats['elite'] = $this->getEliteStatistics($user);
        }

        return $stats;
    }

    /**
     * Личная статистика (Player).
     */
    private function getPersonalStatistics(User $user, array $data): array
    {
        $days = (int) $data['days'];
        $startDate = Carbon::today()->subDays($days - 1)->toDateString();
        $endDate = Carbon::today()->toDateString();
        $totals = DailyChecklist::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                SUM(completed_meetings) as total_meetings,
                SUM(new_clients) as total_clients,
                SUM(new_partners) as total_partners,
                SUM(sales) as total_sales,
                SUM(daily_income) as total_income,
                SUM(CASE WHEN is_completed = 1 AND is_day_off = 0 THEN 1 ELSE 0 END) as active_days_count
            ')->first();

        $totalMeetings = (int) ($totals->total_meetings ?? 0);
        $totalClients  = (int) ($totals->total_clients ?? 0);
        $totalPartners = (int) ($totals->total_partners ?? 0);
        $totalSales    = (int) ($totals->total_sales ?? 0);
        $totalIncome   = (float) ($totals->total_income ?? 0);
        $activeDays    = (int) ($totals->active_days_count ?? 0);

        $activeDaysPercentage = $days > 0 ? round(($activeDays / $days) * 100) : 0;
        $totalVolume = (float) $user->contacts()->sum('volume');

        return [
            'period_days'            => $days,
            'total_meetings'         => $totalMeetings,
            'avg_meetings'           => $days > 0 ? round($totalMeetings / $days, 1) : 0,
            'total_clients'          => $totalClients,
            'avg_clients'            => $days > 0 ? round($totalClients / $days, 1) : 0,
            'total_partners'         => $totalPartners,
            'avg_partners'           => $days > 0 ? round($totalPartners / $days, 1) : 0,
            'total_sales'            => $totalSales,
            'avg_sales'              => $days > 0 ? round($totalSales / $days, 1) : 0,
            'total_income'           => $totalIncome,
            'avg_income'             => $days > 0 ? round($totalIncome / $days, 2) : 0,
            'active_days_count'      => $activeDays,
            'active_days_percentage' => $activeDaysPercentage,
            'total_volume'           => $totalVolume,
        ];
    }

    private function getTeamStatistics(User $leader, array $data): array
    {
        $days = (int) $data['days'];
        $startDate = Carbon::today()->subDays($days - 1)->toDateString();
        $todayStr = Carbon::today()->toDateString();

        $playerIds = $leader->players()->pluck('id')->toArray();
        $totalPlayers = count($playerIds);

        $activePlayersToday = DailyChecklist::whereIn('user_id', $playerIds)
            ->where('date', $todayStr)
            ->where(fn($q) => $q->where('is_completed', true)->orWhere('is_day_off', true))
            ->count();

        $teamTotalVolume = (float) Contact::whereIn('user_id', $playerIds)->sum('volume');

        $ranking = User::whereIn('id', $playerIds)
            ->withSum(['contacts as volume' => function($query) use ($startDate, $todayStr) {
                $query->whereBetween('created_at', [$startDate . ' 00:00:00', $todayStr . ' 23:59:59']);
            }], 'volume')
            ->orderByDesc('volume')
            ->get()
            ->map(function ($player) {
                return [
                    'name' => $player->full_name,
                    'volume' => (int) ($player->volume_sum ?? 0),
                    'diff' => rand(-5, 15) . '%',
                ];
            });

        return [
            'total_players'        => $totalPlayers,
            'active_players_today' => $activePlayersToday,
            'team_total_volume'    => $teamTotalVolume,
            'ranking'              => $ranking,
        ];
    }

    /**
     * Глобальная статистика (Elite).
     */
    private function getEliteStatistics(User $elite): array
    {
        $todayStr = Carbon::today()->toDateString();
        $leaderIds = $elite->players()->pluck('id')->toArray();
        $totalLeaders = count($leaderIds);

        if ($totalLeaders === 0) {
            return [
                'total_leaders'     => 0,
                'active_leaders'    => 0,
                'total_team_volume' => 0.0,
            ];
        }
        $activeLeadersCount = LeadershipChecklist::whereIn('user_id', $leaderIds)
            ->where('date', $todayStr)
            ->where(function ($query) {
                $query->where('is_completed', true)
                    ->orWhere('is_day_off', true);
            })
            ->count();

        $playerIds = User::whereIn('leader_id', $leaderIds)->pluck('id')->toArray();

        $allNetworkIds = array_merge([$elite->id], $leaderIds, $playerIds);
        $totalTeamVolume = (float) Contact::whereIn('user_id', $allNetworkIds)->sum('volume');

        return [
            'total_leaders'     => $totalLeaders,
            'active_leaders'    => $activeLeadersCount,
            'total_team_volume' => $totalTeamVolume,
        ];
    }
}
