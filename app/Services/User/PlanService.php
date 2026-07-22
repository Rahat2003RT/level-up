<?php

namespace App\Services\User;

use App\Models\Contact;
use App\Models\DailyChecklist;
use App\Models\LeadershipChecklist;
use App\Models\PlanPause;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

final class PlanService
{
    public function getStatisticsForUser(User $user, array $data): array
    {
        return $this->getStatistics($user, $data);
    }
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

        if ($leaderTodayChecklist && !$leaderTodayChecklist->is_day_off) {
            $currentDayNumber = $leaderTodayChecklist->day_number;
        } else {
            $maxLeaderDay = LeadershipChecklist::where('user_id', $leader->id)
                ->where('is_day_off', false)
                ->max('day_number') ?? 0;
            $currentDayNumber = $maxLeaderDay + (null ? 0 : 1);
        }
        $currentDayNumber = min(90, $currentDayNumber);

        $players = $leader->players()->with('checklists')->get();
        $totalPlayers = $players->count();

        $activePlayersCount = 0;
        $totalProgressPercentage = 0;

        foreach ($players as $player) {
            /** @var User $player */
            /** @var Collection<DailyChecklist> $playerChecklists */
            $playerChecklists = $player->checklists;

            $playerWorkChecklistsCount = $playerChecklists->where('is_day_off', false)->count();

            $playerTodayChecklist = $player->checklists->first(fn($c) => $c->date->toDateString() === $todayStr);
            if ($playerTodayChecklist && !$playerTodayChecklist->is_day_off) {
                $pDay = $playerTodayChecklist->day_number;
            } else {
                $pDay = $player->checklists->where('is_day_off', false)->max('day_number') ?? 0;
            }

            $playerProgress = $pDay > 0 ? round(($pDay / 90) * 100) : 0;
            $totalProgressPercentage += min(100, $playerProgress);

            if ($playerWorkChecklistsCount > 0 && $playerWorkChecklistsCount >= $pDay) {
                $activePlayersCount++;
            }
        }

        $averageProgress = $totalPlayers > 0 ? round($totalProgressPercentage / $totalPlayers) : 0;

        return [
            'current_day_number' => $currentDayNumber,
            'plan_start_date'    => $leader->created_at ? $leader->created_at->toDateString() : null,
            'players_count'      => $totalPlayers,
            'active_count'       => $activePlayersCount,
            'average_progress'   => min(100, $averageProgress),
        ];
    }

    protected function getUserProgress(int $userId): array
    {
        $wins = DailyChecklist::where('user_id', $userId)
            ->where('is_completed', true)
            ->where('is_day_off', false)
            ->count();

        $todayChecklist = $this->getTodayChecklist($userId);

        if ($todayChecklist && $todayChecklist->is_day_off) {
            $currentDayNumber = DailyChecklist::where('user_id', $userId)
                ->where('is_day_off', false)
                ->max('day_number') ?? 0;
        } else {
            $currentDayNumber = $todayChecklist ? $todayChecklist->day_number : $this->getNextDayNumber($userId);
        }

        $totalCourseDays = 90;
        $percentage = ($currentDayNumber / $totalCourseDays) * 100;
        $percentage = round($percentage);

        $planStartDate = Carbon::parse(
            DailyChecklist::where('user_id', $userId)->where('is_day_off', false)->min('date') ?? Carbon::today()
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
            'current_streak'     => $currentStreak,
            'wins'               => $wins,
            'misses'             => $loses,
            'percentage'         => min(100, max(0, $percentage)),
            'plan_start_date'    => $planStartDate,
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
        return (int)DailyChecklist::where('user_id', $userId)
                ->where('is_day_off', false)
                ->max('day_number') + 1;
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
                    'id'                          => null,
                    'user_id'                     => $user->id,
                    'date'                        => $date,
                    'day_number'                  => $nextDayNumber,
                    'is_completed'                => false,
                    'is_day_off'                  => false,
                    'checked_team_activity'       => false,
                    'contacted_players'           => false,
                    'added_new_player'            => false,
                    'held_online_meeting'         => false,
                    'posted_engaged_social_media' => false,
                    'attracted_new_client'        => false,
                    'brought_new_partner'         => false,
                    'sent_new_invitations'        => false,
                    'is_editable'                 => true,
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
                    'date'                       => $date,
                    'day_number'                 => $nextDayNumber,
                    'is_completed'               => false,
                    'is_day_off'                 => false,
                    'scheduled_meetings'         => 0,
                    'completed_meetings'         => 0,
                    'new_clients'                => 0,
                    'new_partners'               => 0,
                    'business_conversations'     => 0,
                    'presentations'              => 0,
                    'sales'                      => 0,
                    'daily_income'               => 0.0,
                    'social_media_activity'      => false,
                    'communication_with_sponsor' => false,
                    'plans_for_the_day'          => '',
                    'results_for_the_day'        => '',
                    'notes_for_the_day'          => '',
                    'is_editable'                => true,
                ];
            }
        }

        return [];
    }

    /**
     * Создать/заполнить чек-лист за сегодня.
     */
    public function storeChecklist(User $user, array $data): LeadershipChecklist|DailyChecklist
    {
        $today = Carbon::today()->toDateString();
        $model = $user->can('access-leader') ? LeadershipChecklist::class : DailyChecklist::class;

        $record = $model::where('user_id', $user->id)->whereDate('date', $today)->first();

        if ($record) {
            if ($record->is_day_off) {
                throw new AuthorizationException('This day has been selected as a day off. First, remove the day off to fill out the checklist.');
            }
            throw new AuthorizationException('The checklist for today has already been filled out.');
        }

        $dayNumber = (int)$model::where('user_id', $user->id)->where('is_day_off', false)->max('day_number') + 1;

        if ($user->can('access-leader')) {
            /** @var DailyChecklist $checklist */
            $checklist = LeadershipChecklist::create(array_merge($data, [
                'user_id'      => $user->id,
                'date'         => $today,
                'day_number'   => $dayNumber,
                'is_completed' => true,
                'is_day_off'   => false,
            ]));
        } else {
            /** @var DailyChecklist $checklist */
            $checklist = DailyChecklist::create(array_merge($data, [
                'user_id'      => $user->id,
                'date'         => $today,
                'day_number'   => $dayNumber,
                'is_completed' => true,
                'is_day_off'   => false,
            ]));

            $checklist->progress = $this->getProgress($user);
        }

        return $checklist;
    }


    /**
     * Получить сводную статистику пользователя в зависимости от его ролей.
     */
    public function getStatistics(User $user, array $data): array
    {
        $stats = $this->getPersonalStatistics($user, $data);

        if ($user->can('access-elite')) {
            $stats = $this->getEliteStatistics($user);
        }

        return $stats;
    }

    /**
     * Личная статистика (Player).
     */
    private function getPersonalStatistics(User $user, array $data): array
    {
        $days = (int)$data['days'];
        $startDate = $this->getActualStartDate($user->id, $days);
        $endDate = Carbon::today()->format('Y-m-d');
        $totals = DailyChecklist::where('user_id', $user->id)
            ->whereRaw("date(date) BETWEEN ? AND ?", [$startDate, $endDate])
            ->selectRaw('
                SUM(completed_meetings) as total_meetings,
                SUM(new_clients) as total_clients,
                SUM(new_partners) as total_partners,
                SUM(sales) as total_sales,
                SUM(daily_income) as total_income,
                SUM(CASE WHEN is_completed = true AND is_day_off = false THEN 1 ELSE 0 END) as active_days_count
            ')->first();

        $totalMeetings = (int)($totals->total_meetings ?? 0);
        $totalClients = (int)($totals->total_clients ?? 0);
        $totalPartners = (int)($totals->total_partners ?? 0);
        $totalSales = (int)($totals->total_sales ?? 0);
        $totalIncome = (float)($totals->total_income ?? 0);
        $activeDays = (int)($totals->active_days_count ?? 0);

        $activeDaysPercentage = $days > 0 ? round(($activeDays / $days) * 100) : 0;
        $totalVolume = (float)$user->contacts()->sum('volume');

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
        $totalTeamVolume = (float)Contact::whereIn('user_id', $allNetworkIds)->sum('volume');

        return [
            'total_leaders'     => $totalLeaders,
            'active_leaders'    => $activeLeadersCount,
            'total_team_volume' => $totalTeamVolume,
        ];
    }


    /**
     * Выбрать или удалить выходной день с валидацией по мировому времени (UTC-12 ... UTC+14)
     * @throws Exception
     */
    public function toggleDayOff(User $user, string $dateStr): array
    {
        $targetDate = Carbon::parse($dateStr)->startOfDay();
        $serverNow = Carbon::now();
        $earliestEarthDate = $serverNow->clone()->timezone('Etc/GMT+12')->startOfDay();
        $latestEarthDate = $serverNow->clone()->timezone('Etc/GMT-14')->addDays(120)->startOfDay();

        if ($targetDate->lt($earliestEarthDate)) {
            throw new Exception('You cannot change a day off to a past date.');
        }

        if ($targetDate->gt($latestEarthDate)) {
            throw new Exception('You cannot schedule a day off more than 120 days in advance.');
        }

        $model = $user->can('access-leader') ? LeadershipChecklist::class : DailyChecklist::class;
        $record = $model::where('user_id', $user->id)->where('date', $dateStr)->first();

        if ($record) {
            if ($record->is_day_off) {
                $record->delete();
                return ['status' => 'removed', 'date' => $dateStr];
            }
            throw new Exception('The work checklist for this date has already been filled out. It cannot be changed to a day off.');
        }

        $daysOffCount = $model::where('user_id', $user->id)->where('is_day_off', true)->count();
        if ($daysOffCount >= 30) {
            throw new Exception('The limit of days off (30 days) has been exhausted.');
        }

        $dayNumber = 0;

        if ($user->can('access-leader')) {
            LeadershipChecklist::create([
                'user_id' => $user->id,
                'date' => $dateStr,
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
            DailyChecklist::create([
                'user_id' => $user->id,
                'date' => $dateStr,
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
        }

        return ['status' => 'added', 'date' => $dateStr];
    }

    /**
     * Получить список дат выходных и заполненных дней пользователя.
     */
    public function getChecklistDates(User $user): array
    {
        $model = $user->can('access-leader') ? LeadershipChecklist::class : DailyChecklist::class;

        // Достаем только необходимые поля для оптимизации памяти
        $checklists = $model::where('user_id', $user->id)
            ->select(['date', 'is_day_off', 'is_completed'])
            ->get();

        $daysOff = [];
        $activeDays = [];

        foreach ($checklists as $checklist) {
            $dateStr = is_string($checklist->date)
                ? $checklist->date
                : $checklist->date->toDateString();

            if ($checklist->is_day_off) {
                $daysOff[] = $dateStr;
            } elseif ($checklist->is_completed) {
                $activeDays[] = $dateStr;
            }
        }

        return [
            'days_off'    => $daysOff,
            'active_days' => $activeDays,
        ];
    }

    /**
     * Получить командную статистику лидера за указанный период (в днях)
     * с индивидуальным учётом пауз для каждого игрока.
     *
     * @param User $leader
     * @param array $data
     * @return array
     */
    public function getTeamStatistics(User $leader, array $data): array
    {
        abort_if(!$leader->can('access-leader'), 403, 'This action is unauthorized for your role.');

        $days = $data['days'] ?? 30;
        $playerIds = $leader->players()->pluck('id')->toArray();
        $totalPlayers = count($playerIds);

        if ($totalPlayers === 0) {
            return [
                'period_days'          => $days,
                'players_count'        => 0,
                'active_players_count' => 0,
                'total_volume'         => 0.0,
            ];
        }
        $allPauses = PlanPause::whereIn('user_id', $playerIds)
            ->orderBy('started_at', 'desc')
            ->get()
            ->groupBy('user_id');
        $playerStartDates = [];
        foreach ($playerIds as $playerId) {
            $playerPauses = $allPauses->get($playerId) ?? collect();
            $playerStartDates[$playerId] = $this->calculateIndividualStartDate($days, $playerPauses);
        }

        $earliestStartDate = collect($playerStartDates)->min();
        $endDate = Carbon::today()->toDateString();
        $allChecklists = DailyChecklist::whereIn('user_id', $playerIds)
            ->whereBetween('date', [$earliestStartDate, $endDate])
            ->get()
            ->groupBy('user_id');

        $playersWithMissesCount = 0;

        foreach ($playerIds as $playerId) {
            $individualStartDate = $playerStartDates[$playerId];
            $playerChecklists = $allChecklists->get($playerId) ?? collect();
            $hasMisses = $playerChecklists->contains(function ($checklist) use ($individualStartDate) {
                $checklistDate = $checklist->date->toDateString();

                return $checklistDate >= $individualStartDate
                    && !$checklist->is_completed
                    && !$checklist->is_day_off;
            });

            if ($hasMisses) {
                $playersWithMissesCount++;
            }
        }
        $activePlayersCount = $totalPlayers - $playersWithMissesCount;
        $totalVolume = (float)Contact::whereIn('user_id', $playerIds)->sum('volume');

        return [
            'period_days'          => $days,
            'players_count'        => $totalPlayers,
            'active_players_count' => $activePlayersCount,
            'total_volume'         => $totalVolume,
        ];
    }

    /**
     * Вспомогательный метод для расчёта индивидуальной даты начала на основе коллекции пауз.
     */
    private function calculateIndividualStartDate(int $neededDays, \Illuminate\Support\Collection $pauses): string
    {
        $startDate = Carbon::today();
        $collectedDays = 0;

        while ($collectedDays < $neededDays) {
            $dateStr = $startDate->toDateString();

            $isPaused = $pauses->contains(function ($pause) use ($dateStr) {
                $startedAt = $pause->started_at->toDateString();
                $endedAt = $pause->ended_at ? $pause->ended_at->toDateString() : null;

                return $dateStr >= $startedAt && (is_null($endedAt) || $dateStr <= $endedAt);
            });

            if (!$isPaused) {
                $collectedDays++;
            }

            if ($collectedDays < $neededDays) {
                $startDate->subDay();
            }
        }

        return $startDate->toDateString();
    }

    /**
     * ОПТИМИЗИРОВАНО: Вычисляет дату начала периода без N+1 запросов.
     */
    private function getActualStartDate(int $userId, int $neededDays): string
    {
        $startDate = Carbon::today();
        $collectedDays = 0;

        $pauses = PlanPause::where('user_id', $userId)
            ->orderBy('started_at', 'desc')
            ->get();

        while ($collectedDays < $neededDays) {
            $dateStr = $startDate->toDateString();
            $isPaused = $pauses->contains(function ($pause) use ($dateStr) {
                $startedAt = $pause->started_at->toDateString();
                $endedAt = $pause->ended_at ? $pause->ended_at->toDateString() : null;

                return $dateStr >= $startedAt && (is_null($endedAt) || $dateStr <= $endedAt);
            });

            if (!$isPaused) {
                $collectedDays++;
            }

            if ($collectedDays < $neededDays) {
                $startDate->subDay();
            }
        }

        return $startDate->toDateString();
    }
}
