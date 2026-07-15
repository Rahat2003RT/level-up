<?php

namespace App\Services\User;

use App\Models\DailyChecklist;
use App\Models\LeadershipChecklist;
use App\Models\User;
use Carbon\Carbon;
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
}
