<?php

namespace App\Services\User;

use App\Models\Contact;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EliteService
{
    /**
     * Генерация ссылки приглашения для Элиты.
     */
    public function generateInvitation(User $elite): string
    {
        // Проверяем роль на всякий случай
        if ($elite->role?->value !== 'elite' && $elite->role !== 'elite') {
            throw ValidationException::withMessages(['role' => 'Only Elite users can generate this link.']);
        }

        $invitation = TeamInvitation::updateOrCreate(
            ['leader_id' => $elite->id], // В поле leader_id пишем ID нашей элиты
            [
                'token' => Str::random(32),
                'expires_at' => Carbon::now()->addHours(3), // Ссылка живет 3 часа
            ]
        );

        return config('app.url') . "/elite/team/" . $invitation->token;
    }

    /**
     * Получить пагинированный список лидеров для Элиты.
     */
    public function getTeamLeaders(User $elite, array $data): LengthAwarePaginator
    {
        $todayStr = Carbon::today()->toDateString();
        $perPage = $data['per_page'] ?? 15;

        // Получаем лидеров, привязанных к этой элите
        $leadersPaginator = $elite->players()
            ->where('role', 'leader')
            ->with(['checklists' => fn($q) => $q->latest('date')])
            ->latest('created_at')
            ->paginate($perPage);

        // Трансформируем элементы (логика подсчета метрик остаётся прежней)
        $transformedItems = collect($leadersPaginator->items())->map(function ($leader) use ($todayStr) {
            $lastLeaderChecklist = $leader->checklists->first();
            $todayLeaderChecklist = $leader->checklists->first(fn($c) => $c->date->toDateString() === $todayStr);

            if ($todayLeaderChecklist) {
                $currentDayNumber = $todayLeaderChecklist->day_number;
            } else {
                $maxLeaderDay = $leader->checklists->max('day_number') ?? 0;
                $currentDayNumber = $maxLeaderDay + 1;
            }
            $currentDayNumber = min(90, $currentDayNumber);

            $leaderStatus = 'Inactive';
            if ($lastLeaderChecklist && (bool)$lastLeaderChecklist->is_completed && !($lastLeaderChecklist->is_day_off ?? false)) {
                $leaderStatus = 'Active';
            }

            $players = $leader->players()->with('checklists')->get();
            $totalPlayers = $players->count();
            $activePlayersCount = 0;

            foreach ($players as $player) {
                $playerChecklistsCount = $player->checklists->count();
                $playerTodayChecklist = $player->checklists->first(fn($c) => $c->date->toDateString() === $todayStr);

                if ($playerTodayChecklist) {
                    $pDay = $playerTodayChecklist->day_number;
                } else {
                    $pDay = $player->checklists->max('day_number') ?? 0;
                }

                if ($playerChecklistsCount > 0 && $playerChecklistsCount >= $pDay) {
                    $activePlayersCount++;
                }
            }

            $leaderPlayerIds = $players->pluck('id')->toArray();
            $allTeamUserIds = array_merge([$leader->id], $leaderPlayerIds);
            $monthlyVolume = (int) Contact::whereIn('user_id', $allTeamUserIds)->sum('volume');

            return [
                'id'                   => $leader->id,
                'name'                 => $leader->name . ' ' . $leader->surname,
                'avatar'               => $leader->avatar_url ?? null,
                'current_day_number'   => $currentDayNumber,
                'status'               => $leaderStatus,
                'total_players_count'  => $totalPlayers,
                'active_players_count' => $activePlayersCount,
                'monthly_volume'       => $monthlyVolume,
            ];
        });

        return new LengthAwarePaginator(
            $transformedItems,
            $leadersPaginator->total(),
            $leadersPaginator->perPage(),
            $leadersPaginator->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}
