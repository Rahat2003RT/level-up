<?php

namespace App\Services\User;

use App\Models\Contact;
use App\Models\LeadershipChecklist;
use App\Models\TeamInvitation;
use App\Models\TeamPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeaderService
{
    public function removePlayerFromTeam(User $leader, User $player): bool
    {
        if ($player->leader_id !== $leader->id) {
            throw ValidationException::withMessages(['player' => 'This player is not on your team.']);
        }
        return $player->update(['leader_id' => null]);
    }


    /**
     * Получить контакты пользователя по определенному типу.
     *
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
     * Создать новый контакт.
     *
     * @param User $user
     * @param array $data
     * @return Contact
     */
    public function createContact(User $user, array $data): Contact
    {
        return $user->contacts()->create($data);
    }

    /**
     * Обновить данные существующего контакта.
     *
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
     * Удалить контакт.
     *
     * @param Contact $contact
     * @return bool|null
     */
    public function deleteContact(Contact $contact): ?bool
    {
        return $contact->delete();
    }


    /**
     * Получить чек-лист лидера за день или вернуть структуру-заглушку.
     */
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
                'id'                          => null,
                'user_id'                     => $userId,
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
     * Получить командный план для пользователя (лидера или игрока этой команды).
     */
    public function getTeamPlan(User $user): Model
    {
        $leaderId = $user->isLeader() ? $user->id : $user->leader_id;

        if (!$leaderId) {
            return new TeamPlan([
                'daily_calls' => 0,
                'daily_meetings' => 0,
                'business_conversations' => 0,
                'presentations' => 0,
                'social_media_posts' => 0,
                'new_clients_per_week' => 0,
                'new_partners_per_week' => 0,
                'daily_volume_points' => 0,
            ]);
        }

        return TeamPlan::firstOrCreate(
            ['user_id' => $leaderId],
            [
                'daily_calls' => 0,
                'daily_meetings' => 0,
                'business_conversations' => 0,
                'presentations' => 0,
                'social_media_posts' => 0,
                'new_clients_per_week' => 0,
                'new_partners_per_week' => 0,
                'daily_volume_points' => 0,
            ]
        );
    }

    /**
     * Обновить или создать командный план.
     */
    public function updateTeamPlan(User $leader, array $data): Model
    {
        return TeamPlan::updateOrCreate(
            ['user_id' => $leader->id],
            $data
        );
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
            'plan_start_date'    => $leader->created_at ? $leader->created_at->toDateString() : null,
            'players_count'      => $totalPlayers,
            'active_count'       => $activePlayersCount,
            'average_progress'   => min(100, $averageProgress),
        ];
    }

    /**
     * Получить информацию об Элите по токену ссылки (для экрана подтверждения).
     */
    public function getEliteTeamDataByToken(User $user, string $token): array
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (!$invitation) {
            throw ValidationException::withMessages(['token' => 'Invitation not found.']);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'Link has expired.']);
        }

        // КРИТИЧЕСКОЕ ПРАВИЛО: Только Лидер может вступать к Элите
        if ($user->role?->value !== 'leader' && $user->role !== 'leader') {
            throw ValidationException::withMessages(['role' => 'Only users with the Leader role can join an Elite team.']);
        }

        if ($invitation->leader_id === $user->id) {
            throw ValidationException::withMessages(['team' => 'You cannot join your own team.']);
        }

        if ($user->leader_id === $invitation->leader_id) {
            throw ValidationException::withMessages(['team' => 'You are already a member of this team.']);
        }

        if (!is_null($user->leader_id)) {
            throw ValidationException::withMessages(['team' => 'You are already in an Elite team. Leave your current team first.']);
        }

        return [
            'elite_name' => $invitation->leader->name,
            'elite_avatar' => $invitation->leader->avatar_url ?? null,
            'token' => $token
        ];
    }

    /**
     * Обработка принятия или отклонения инвайта Лидером.
     */
    public function handleInvitation(User $user, string $token, bool $accept): array
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (!$invitation || $invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'The link is invalid or expired.']);
        }

        if ($user->role?->value !== 'leader' && $user->role !== 'leader') {
            throw ValidationException::withMessages(['role' => 'Only users with the Leader role can join an Elite team.']);
        }

        if ($invitation->leader_id === $user->id) {
            throw ValidationException::withMessages(['token' => 'You cannot join your own team.']);
        }

        if (!is_null($user->leader_id)) {
            throw ValidationException::withMessages(['token' => 'You are already in a team. Leave your current team first.']);
        }

        if ($accept) {
            $user->update(['leader_id' => $invitation->leader_id]);
            return ['status' => 'accepted', 'message' => 'You have successfully joined the Elite team.'];
        }

        return ['status' => 'declined', 'message' => 'You declined the invitation.'];
    }
}
