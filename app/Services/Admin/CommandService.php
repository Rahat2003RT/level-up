<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class CommandService
{
    public function getCommandsList(array $filters): LengthAwarePaginator
    {
        $role = $filters['role'] ?? null;
        $perPage = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;

        $query = User::whereIn('role', [UserRole::ELITE, UserRole::LEADER])
            ->withCount('players as members_count')
            ->when($role, function ($query) use ($role) {
                return $query->where('role', $role);
            })
            ->when($filters['query'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'ILIKE', "%$search%")
                        ->orWhere('surname', 'ILIKE', "%$search%");
                });
            });

        return $query->latest()->paginate($perPage, ['*'], 'page', $page);
    }

    public function getCommandDetails(User $leaderOrElite): array
    {
        $eliteName = null;

        if ($leaderOrElite->role === UserRole::LEADER) {
            if (!$leaderOrElite->relationLoaded('leader')) {
                $leaderOrElite->load('leader');
            }

            $eliteName = $leaderOrElite->leader?->name;
        }

        $members = $leaderOrElite->players()
            ->withSum('contacts as volume', 'volume')
            ->get()
            ->map(fn($member) => [
                'id'     => $member->id,
                'name'   => $member->name . ' ' . $member->surname,
                'avatar' => $member->avatar_path ?? null,
                'role'   => $member->role?->value,
                'volume' => (int)($member->volume ?? 0),
            ]);

        return [
            'id'          => $leaderOrElite->id,
            'leader_name' => $leaderOrElite->role === UserRole::LEADER ? $leaderOrElite->name : null,
            'elite_name'  => $leaderOrElite->role === UserRole::ELITE ? $leaderOrElite->name : $eliteName,
            'role'        => $leaderOrElite->role?->value,
            'members'     => $members
        ];
    }

    /**
     * 3. Метод удаления пользователя из команды
     */
    public function removeMember(User $member): bool
    {
        if (is_null($member->leader_id)) {
            throw ValidationException::withMessages(['member' => 'This user is not a member of any team.']);
        }

        return $member->update(['leader_id' => null]);
    }

    public function addMember(User $leaderOrElite, User $member): bool
    {
        if ($leaderOrElite->role === UserRole::LEADER && $member->role !== UserRole::PLAYER) {
            throw ValidationException::withMessages(['member' => 'Only Players can be added to a Leader team.']);
        }

        if ($leaderOrElite->role === UserRole::ELITE && $member->role !== UserRole::LEADER) {
            throw ValidationException::withMessages(['member' => 'Only Leaders can be added to an Elite team.']);
        }

        if ($member->id === $leaderOrElite->id) {
            throw ValidationException::withMessages(['member' => 'Cannot add a user to their own team.']);
        }

        return $member->update(['leader_id' => $leaderOrElite->id]);
    }

    public function searchAvailableUsers(User $leaderOrElite, array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 20;
        $search = isset($filters['query']) ? trim((string)$filters['query']) : null;

        $targetRole = $leaderOrElite->role === UserRole::ELITE ? UserRole::LEADER : UserRole::PLAYER;

        return User::where('role', $targetRole)
            ->whereNull('leader_id')
            ->where('id', '!=', $leaderOrElite->id)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('surname', 'ILIKE', "%{$search}%");
                });
            })
            ->paginate($perPage);
    }
}
