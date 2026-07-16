<?php

namespace App\Services\User;

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
            $exists = LeadershipChecklist::where('user_id', $user->id)->where('date', $today)->exists();
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
            $exists = DailyChecklist::where('user_id', $user->id)->where('date', $today)->exists();
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
}
