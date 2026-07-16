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


    public function getOrCreateVirtual(User $user, array $data): LeadershipChecklist|array|null
    {
        $date = $data['date'];
        $userId = $user->id;
        $today = Carbon::today()->toDateString();

        /** @var LeadershipChecklist|null $checklist */
        $checklist = LeadershipChecklist::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if ($checklist) {
            $checklist->is_editable = $checklist->isEditable();
            return $checklist;
        }

        if ($date === $today) {
            $nextDayNumber = LeadershipChecklist::where('user_id', $userId)->max('day_number') + 1;

            return [
                'id' => null,
                'user_id' => $userId,
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

        return [];
    }

    /**
     * Сохранить чек-лист лидера на сегодня.
     * @throws AuthorizationException
     */
    public function storeAndCompleteToday(User $user, array $data): LeadershipChecklist
    {
        if ($this->getTodayChecklist($user->id)) {
            throw new AuthorizationException('The leadership checklist for today has already been completed.');
        }

        $dayNumber = $this->getNextDayNumber($user->id);

        /** @var LeadershipChecklist $checklist */
        $checklist = LeadershipChecklist::create(array_merge($data, [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'day_number' => $dayNumber,
            'is_completed' => true,
            'is_day_off' => false,
        ]));

        return $checklist;
    }

    /**
     * Установить лидера статус "Выходной" на сегодня.
     * @throws AuthorizationException
     */
    public function setDayOffToday(User $user): LeadershipChecklist
    {
        if ($this->getTodayChecklist($user->id)) {
            throw new AuthorizationException("Today's leadership checklist has already been recorded.");
        }

        $dayNumber = $this->getNextDayNumber($user->id);

        /** @var LeadershipChecklist $checklist */
        $checklist = LeadershipChecklist::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
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

        return $checklist;
    }

    protected function getTodayChecklist(int $userId): ?LeadershipChecklist
    {
        return LeadershipChecklist::where('user_id', $userId)
            ->where('date', Carbon::today()->toDateString())
            ->first();
    }

    protected function getNextDayNumber(int $userId): int
    {
        return LeadershipChecklist::where('user_id', $userId)->max('day_number') + 1;
    }

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

        // Получаем ID всех игроков команды
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
