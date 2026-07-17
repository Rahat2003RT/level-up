<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class CommandService
{
    /**
     * 1. Метод получения команд (команд игроков и команд лидеров)
     */
    public function getCommandsList(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 20;

        // Команда существует у любого, кто является Elite или Leader
        $query = User::whereIn('role', [UserRole::ELITE, UserRole::LEADER])
            ->withCount('players as members_count')
            ->when($filters['query'] ?? null, function ($q, $search) {
                $searchLower = mb_strtolower($search, 'UTF-8');
                $q->whereRaw('LOWER(name) LIKE ?', ["%$searchLower%"]);
            });

        return $query->latest()->paginate($perPage);
    }

    /**
     * 2. Метод получения конкретной команды и её участников
     */
    public function getCommandDetails(User $leaderOrElite): array
    {
        // Находим вышестоящего (если текущий — Leader, у него может быть Elite)
        $eliteName = null;
        if ($leaderOrElite->role === UserRole::LEADER && $leaderOrElite->leader_id) {
            $eliteName = User::where('id', $leaderOrElite->leader_id)->value('name');
        }

        // Получаем список участников без самого лидера/элиты
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
            throw ValidationException::withMessages(['member' => 'Этот пользователь и так не состоит в команде.']);
        }

        return $member->update(['leader_id' => null]);
    }

    /**
     * 4. Метод добавления в команду (административно)
     */
    public function addMember(User $leaderOrElite, User $member): bool
    {
        // Проверяем правила иерархии
        if ($leaderOrElite->role === UserRole::LEADER && $member->role !== UserRole::PLAYER) {
            throw ValidationException::withMessages(['member' => 'В команду Лидера можно добавлять только Игроков (Player).']);
        }

        if ($leaderOrElite->role === UserRole::ELITE && $member->role !== UserRole::LEADER) {
            throw ValidationException::withMessages(['member' => 'В команду Элиты можно добавлять только Лидеров (Leader).']);
        }

        if ($member->id === $leaderOrElite->id) {
            throw ValidationException::withMessages(['member' => 'Нельзя добавить пользователя в свою собственную команду.']);
        }

        return $member->update(['leader_id' => $leaderOrElite->id]);
    }

    /**
     * 5. Метод поиска пользователей, которых можно добавить в команду
     */
    public function searchAvailableUsers(User $leaderOrElite, array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 20;
        $search = $filters['query'] ?? null;

        // Определяем, кого мы ищем для этой команды
        $targetRole = $leaderOrElite->role === UserRole::ELITE ? UserRole::LEADER : UserRole::PLAYER;

        return User::where('role', $targetRole)
            ->whereNull('leader_id') // Не в команде
            ->where('id', '!=', $leaderOrElite->id) // Исключаем самого владельца
            ->when($search, function ($q) use ($search) {
                $searchLower = mb_strtolower($search, 'UTF-8');
                $q->where(function ($sub) use ($searchLower) {
                    $sub->whereRaw('LOWER(name) LIKE ?', ["%$searchLower%"])
                        ->orWhereRaw('LOWER(surname) LIKE ?', ["%$searchLower%"]);
                });
            })
            ->paginate($perPage);
    }
}
