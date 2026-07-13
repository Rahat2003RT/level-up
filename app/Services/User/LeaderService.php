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
     * @param User $user
     * @param array $data
     * @return array
     */
    public function getContacts(User $user, array $data): array
    {
        $contactsQuery = $user->contacts();
        $contactsPaginator = $contactsQuery
            ->when($data['query'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%$search%");
            })
            ->latest()
            ->paginate($data['limit'] ?? 20);

        $totalVolume = (float)$user->contacts()->sum('volume');

        return [
            'contacts' => $contactsPaginator,
            'total_volume' => $totalVolume,
        ];
    }

    /**
     * @param User $user
     * @param array $data
     * @return Contact
     */
    public function createContact(User $user, array $data): Contact
    {
        return $user->contacts()->create($data);
    }

    /**
     * @param Contact $contact
     * @param array $data
     * @return Contact
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        $contact->update($data);
        return $contact;
    }

    /**
     * @param User $user
     * @param Contact $contact
     * @return bool|null
     * @throws AuthorizationException
     */
    public function deleteContact(User $user, Contact $contact): ?bool
    {
        if ($contact->user_id !== $user->id) {
            throw new AuthorizationException('You do not own this contact.');
        }
        return $contact->delete();
    }


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
     * Получить агрегированную статистику для главной страницы лидера.
     */
    public function getDashboardStatistics(User $leader): array
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
