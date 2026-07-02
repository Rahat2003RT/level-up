<?php
namespace App\Services\User;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\DailyChecklist;
use Carbon\Carbon;
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

    public function getTeamDataByToken(User $user, string $token): array
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (!$invitation) {
            throw ValidationException::withMessages(['token' => 'Invitation not found.']);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'Link has expired.']);
        }

        if ($user->role != UserRole::PLAYER) {
            throw ValidationException::withMessages(['role' => 'Only users with the Player role can join a team.']);
        }

        if ($user->leader_id === $invitation->leader_id) {
            throw ValidationException::withMessages(['team' => 'You are already a member of this team.']);
        }

        return [
            'leader_name' => $invitation->leader->name,
            'leader_avatar' => $invitation->leader->avatar_url ?? null,
            'token' => $token
        ];
    }

    /**
     * 4. Принять или отказаться от инвайта
     */
    public function handleInvitation(User $user, string $token, bool $accept): array
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (!$invitation || $invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'The link is invalid or expired.']);
        }

        if ($accept) {
            $user->update(['leader_id' => $invitation->leader_id]);
            return ['status' => 'accepted', 'message' => 'You have successfully joined the team.'];
        }

        return ['status' => 'declined', 'message' => 'You declined the invitation.'];
    }

    public function getTeamMembers(User $leader, array $filters): LengthAwarePaginator
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
                $query->where('date', $todayStr);
            }])
            ->latest()
            ->paginate($filters['limit'] ?? 15)
            ->through(function ($player) use ($todayStr) {
                $todayChecklist = $player->checklists->first();
                if ($todayChecklist) {
                    $currentDayNumber = $todayChecklist->day_number;
                    $isCompletedToday = (bool)$todayChecklist->is_completed;
                } else {
                    $currentDayNumber = DailyChecklist::where('user_id', $player->id)->max('day_number') + 1;
                    $isCompletedToday = false;
                }

                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'avatar' => $player->avatar_url ?? null,
                    'current_day_number' => $currentDayNumber,
                    'clients_count' => $player->clients_count,
                    'partners_count' => $player->partners_count,
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
}
