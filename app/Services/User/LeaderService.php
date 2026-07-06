<?php
namespace App\Services\User;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\LeadershipChecklist;
use App\Models\TeamInvitation;
use App\Models\TeamPlan;
use App\Models\User;
use App\Models\DailyChecklist;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LeaderService
{
    public function generateInvitation(User $leader): string
    {
        $invitation = TeamInvitation::updateOrCreate(
            ['leader_id' => $leader->id],
            [
                'token' => Str::random(32),
                'expires_at' => Carbon::now()->addHours(3),
            ]
        );

        return config('app.url') . "/team/" . $invitation->token;
    }


    /**
     * Получения списка участников команды (без пагинации)
     * @param User $leader
     * @param array $filters
     * @return Collection
     */
    public function getTeamMembers(User $leader, array $filters): Collection
    {
        $todayStr = Carbon::today()->toDateString();

        return $leader->players()
            ->when($filters['query'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%$search%");
            })
            ->withCount([
                'contacts as clients_count' => function ($query) {
                    $query->where('type', 'client');
                },
                'contacts as partners_count' => function ($query) {
                    $query->where('type', 'partner');
                }
            ])
            ->with(['checklists' => function ($query) use ($todayStr) {
                // Подгружаем чек-лист на сегодня
                $query->where('date', $todayStr);
            }])
            ->with('checklists')
            ->latest()
            ->get()
            ->map(function ($player) use ($todayStr) {
                $todayChecklist = $player->checklists->first(fn($c) => $c->date->toDateString() === $todayStr);

                if ($todayChecklist) {
                    $currentDayNumber = $todayChecklist->day_number;
                    $isCompletedToday = (bool)$todayChecklist->is_completed;
                } else {
                    $maxDay = $player->checklists->max('day_number') ?? 0;
                    $currentDayNumber = $maxDay + 1;
                    $isCompletedToday = false;
                }

                $totalCourseDays = 90;
                $percentage = round(($currentDayNumber / $totalCourseDays) * 100);
                $percentage = min(100, max(0, $percentage));

                return [
                    'id'                 => $player->id,
                    'name'               => $player->name,
                    'avatar'             => $player->avatar_url ?? null,
                    'current_day_number' => $currentDayNumber,
                    'progress_percent'   => $percentage,
                    'clients_count'      => $player->clients_count,
                    'partners_count'     => $player->partners_count,
                    'is_completed_today' => $isCompletedToday,
                ];
            });
    }

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
                'notes_for_the_day'           => '',
                'is_editable'                 => true,
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
            'user_id'      => $user->id,
            'date'         => Carbon::today()->toDateString(),
            'day_number'   => $dayNumber,
            'is_completed' => true,
            'is_day_off'   => false,
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
            'user_id'                     => $user->id,
            'date'                        => Carbon::today()->toDateString(),
            'day_number'                  => $dayNumber,
            'is_completed'                => false,
            'is_day_off'                  => true,
            'checked_team_activity'       => false,
            'contacted_players'           => false,
            'added_new_player'            => false,
            'held_online_meeting'         => false,
            'posted_engaged_social_media' => false,
            'attracted_new_client'        => false,
            'brought_new_partner'         => false,
            'sent_new_invitations'        => false,
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
        $leaderId = $user->role == 'leader' ? $user->id : $user->leader_id;

        if (!$leaderId) {
            return new TeamPlan([
                'daily_calls'            => 0,
                'daily_meetings'         => 0,
                'business_conversations' => 0,
                'presentations'          => 0,
                'social_media_posts'     => 0,
                'new_clients_per_week'   => 0,
                'new_partners_per_week'  => 0,
                'daily_volume_points'    => 0,
            ]);
        }

        return TeamPlan::firstOrCreate(
            ['user_id' => $leaderId],
            [
                'daily_calls'            => 0,
                'daily_meetings'         => 0,
                'business_conversations' => 0,
                'presentations'          => 0,
                'social_media_posts'     => 0,
                'new_clients_per_week'   => 0,
                'new_partners_per_week'  => 0,
                'daily_volume_points'    => 0,
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
}
