<?php
namespace App\Services\User;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\DailyChecklist;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LeaderService
{
    /**
     * 1 & 2. Генерация ссылки на 3 часа
     */
    public function generateInvitation(User $leader): string
    {
        // Каждому лидеру генерируем или обновляем токен
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
     * 3. Получение данных команды по токену инвайта + Валидация
     */
    public function getTeamDataByToken(User $user, string $token): array
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (!$invitation) {
            throw ValidationException::withMessages(['token' => 'Приглашение не найдено.']);
        }

        // Валидация: ссылка устарела
        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'Срок действия ссылки истек.']);
        }

        // Валидация: другая роль (проверяем, например, что у игрока роль player, а не admin/leader)
        // Если у вас роли хранятся в поле `role`, адаптируйте под себя:
        if ($user->role !== 'player') {
            throw ValidationException::withMessages(['role' => 'Только пользователи с ролью Игрок могут вступать в команду.']);
        }

        // Валидация: вы уже отказались (или уже состоите)
        if ($user->leader_id === $invitation->leader_id) {
            throw ValidationException::withMessages(['team' => 'Вы уже состоите в этой команде.']);
        }

        return [
            'leader_name' => $invitation->leader->name,
            'leader_avatar' => $invitation->leader->avatar_url ?? null, // адаптируйте под ваше поле аватарки
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
            throw ValidationException::withMessages(['token' => 'Ссылка недействительна или устарела.']);
        }

        if ($accept) {
            $user->update(['leader_id' => $invitation->leader_id]);
            return ['status' => 'accepted', 'message' => 'Вы успешно вступили в команду.'];
        }

        // В случае отказа, логика "вы уже отказались" из ТЗ подразумевает,
        // что мы можем либо пометить где-то отказ, либо просто ничего не делать.
        // Обычно достаточно вернуть статус.
        return ['status' => 'declined', 'message' => 'Вы отклонили приглашение.'];
    }

    /**
     * 5. Получение списка участников команды Лидера (+ пагинация и поиск)
     * С подсчетом необходимых по ТЗ метрик (день плана, клиенты, партнеры, чек-лист на сегодня)
     */
    public function getTeamMembers(User $leader, array $filters): LengthAwarePaginator
    {
        $todayStr = Carbon::today()->toDateString();

        return $leader->players()
            ->when($filters['query'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            // Подтягиваем количество клиентов и партнеров из контактов
            ->withCount([
                'contacts as clients_count' => function ($query) {
                    $query->where('type', 'client'); // или как у вас в бд называется тип
                },
                'contacts as partners_count' => function ($query) {
                    $query->where('type', 'partner');
                }
            ])
            // Подтягиваем сегодняшний чек-лист, чтобы узнать завершил ли его и какой текущий день плана
            ->with(['checklists' => function ($query) use ($todayStr) {
                $query->where('date', $todayStr);
            }])
            ->latest()
            ->paginate($filters['limit'] ?? 15)
            ->through(function ($player) use ($todayStr) {
                $todayChecklist = $player->checklists->first();

                // Рассчитываем номер текущего дня аналогично PlayerService
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

    /**
     * 6. Удаление пользователя из команды
     */
    public function removePlayerFromTeam(User $leader, User $player): bool
    {
        if ($player->leader_id !== $leader->id) {
            throw ValidationException::withMessages(['player' => 'Этот игрок не состоит в вашей команде.']);
        }

        return $player->update(['leader_id' => null]);
    }
}
