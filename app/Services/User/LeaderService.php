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
