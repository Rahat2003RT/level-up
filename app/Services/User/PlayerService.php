<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Contact;
use App\Models\DailyChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PlayerService
{
    /**
     * Получить существующий чек-лист или подготовить данные для нового.
     *
     * @param User $user
     * @param array $data
     * @return DailyChecklist|array|null
     */
    public function getOrCreateVirtual(User $user, array $data): DailyChecklist|array|null
    {
        $date = $data['date'];
        $userId = $user->id;
        $today = Carbon::today()->toDateString();
        /** @var  DailyChecklist $checklist */
        $checklist = DailyChecklist::where('user_id', $userId)
            ->where('date', $date)
            ->first();
        /** @var DailyChecklist|null $checklist */
        $progress = $this->getUserProgress($user->id);

        if ($checklist) {
            $checklist->progress = $progress;
            $checklist->is_editable = $checklist->isEditable();
            return $checklist;
        }

        if ($date === $today) {
            $nextDayNumber = DailyChecklist::where('user_id', $userId)->max('day_number') + 1;

            return [
                'date' => $date,
                'day_number' => $nextDayNumber,
                'is_completed' => false,
                'is_day_off' => false,
                'scheduled_meetings' => 0,
                'completed_meetings' => 0,
                'new_clients' => 0,
                'new_partners' => 0,
                'business_conversations' => 0,
                'presentations' => 0,
                'sales' => 0,
                'daily_income' => 0.0,
                'social_media_activity' => false,
                'communication_with_sponsor' => false,
                'plans_for_the_day' => '',
                'results_for_the_day' => '',
                'notes_for_the_day' => '',
                'progress' => $progress,
                'is_editable' => true,
            ];
        }
        return [];
    }

    /**
     * Создать чек-лист на сегодня.
     *
     * @param User $user
     * @param array $data
     * @return DailyChecklist
     * @throws AuthorizationException
     */
    public function storeAndCompleteToday(User $user, array $data): DailyChecklist
    {
        $checklist = $this->getTodayChecklist($user->id);
        if ($checklist) {
            throw new AuthorizationException('The checklist for today has already been completed and cannot be edited.');
        }
        $dayNumber = $this->getNextDayNumber($user->id);
        $updatedChecklist = DailyChecklist::create(array_merge($data, [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'day_number' => $dayNumber,
            'is_completed' => true,
            'is_day_off' => false,
        ]));
        /** @var DailyChecklist $updatedChecklist */
        $updatedChecklist->progress = $this->getUserProgress($user->id);

        return $updatedChecklist;
    }

    /**
     * Установить выходной день.
     * @throws AuthorizationException
     */
    public function setDayOffToday(User $user): DailyChecklist
    {
        $checklist = $this->getTodayChecklist($user->id);

        if ($checklist) {
            throw new AuthorizationException("Today's checklist has already been recorded.");
        }

        $dayNumber = $this->getNextDayNumber($user->id);

        $updatedChecklist = DailyChecklist::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'day_number' => $dayNumber,
            'is_completed' => false,
            'is_day_off' => true,
            'scheduled_meetings' => 0,
            'completed_meetings' => 0,
            'new_clients' => 0,
            'new_partners' => 0,
            'business_conversations' => 0,
            'presentations' => 0,
            'sales' => 0,
            'daily_income' => 0,
            'social_media_activity' => false,
            'communication_with_sponsor' => false
        ]);
        /** @var DailyChecklist $updatedChecklist */
        $updatedChecklist->progress = $this->getUserProgress($user->id);
        return $updatedChecklist;
    }

    /**
     * Вспомогательный метод для получения чек-листа на сегодня.
     */
    protected function getTodayChecklist(int $userId): ?DailyChecklist
    {
        return DailyChecklist::where('user_id', $userId)
            ->where('date', Carbon::today()->toDateString())
            ->first();
    }

    /**
     * Вспомогательный метод для расчета следующего номера дня.
     */
    protected function getNextDayNumber(int $userId): int
    {
        return DailyChecklist::where('user_id', $userId)->max('day_number') + 1;
    }

    /**
     * Получить агрегированную статистику игрока за указанный период дней.
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    public function getStatistics(User $user, array $data): array
    {
        $days = $data['days'];
        $startDate = Carbon::today()->subDays($days - 1)->toDateString();
        $endDate = Carbon::today()->toDateString();

        $totals = DailyChecklist::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                DB::raw('SUM(completed_meetings) as total_meetings'),
                DB::raw('SUM(new_clients) as total_clients'),
                DB::raw('SUM(new_partners) as total_partners'),
                DB::raw('SUM(sales) as total_sales'),
                DB::raw('SUM(daily_income) as total_income'),
                DB::raw('COUNT(CASE WHEN is_completed = true AND is_day_off = false THEN 1 END) as active_days_count')
            ])
            ->first();

        $totalMeetings = $totals->total_meetings ?? 0;
        $totalClients = $totals->total_clients ?? 0;
        $totalPartners = $totals->total_partners ?? 0;
        $totalSales = $totals->total_sales ?? 0;
        $totalIncome = $totals->total_income ?? 0;
        $activeDays = $totals->active_days_count ?? 0;

        $activeDaysPercentage = $days > 0 ? ($activeDays / $days) * 100 : 0;

        $totalVolume = $totalIncome;

        return [
            'period_days' => $days,
            'total_meetings' => $totalMeetings,
            'avg_meetings' => $totalMeetings / $days,
            'total_clients' => $totalClients,
            'avg_clients' => $totalClients / $days,
            'total_partners' => $totalPartners,
            'avg_partners' => $totalPartners / $days,
            'total_sales' => $totalSales,
            'avg_sales' => $totalSales / $days,
            'total_income' => $totalIncome,
            'avg_income' => $totalIncome / $days,
            'active_days_count' => $activeDays,
            'active_days_percentage' => $activeDaysPercentage,
            'total_volume' => $totalVolume,
        ];
    }

    /**
     * Прогресс игрока.
     *
     * @param int $userId
     * @return array
     */
    public function getUserProgress(int $userId): array
    {
        $wins = DailyChecklist::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();
        $todayChecklist = $this->getTodayChecklist($userId);
        $currentDayNumber = $todayChecklist ? $todayChecklist->day_number : $this->getNextDayNumber($userId);

        $totalCourseDays = 90;
        $percentage = ($currentDayNumber / $totalCourseDays) * 100;
        $percentage = round($percentage);

        $planStartDate = Carbon::parse(
            DailyChecklist::where('user_id', $userId)->min('date') ?? Carbon::today()
        )->startOfDay()->toIso8601String();

        $loses = DailyChecklist::where('user_id', $userId)
            ->where('date', '<', Carbon::today()->toDateString())
            ->where('is_completed', false)
            ->where('is_day_off', false)
            ->count();

        $currentStreak = 0;
        $checklists = DailyChecklist::where('user_id', $userId)
            ->where('date', '<=', Carbon::today()->toDateString())
            ->orderBy('date', 'desc')
            ->get();

        /** @var Collection<DailyChecklist> $checklists */
        foreach ($checklists as $checklist) {
            if ($checklist->date == Carbon::today()->toDateString() && !$checklist->is_completed && !$checklist->is_day_off) {
                continue;
            }

            if ($checklist->is_completed || $checklist->is_day_off) {
                $currentStreak++;
            } else {
                break;
            }
        }

        return [
            'current_streak' => $currentStreak,
            'wins' => $wins,
            'misses' => $loses,
            'percentage' => min(100, max(0, $percentage)),
            'plan_start_date' => $planStartDate,
            'current_day_number' => $currentDayNumber,
        ];
    }


    /**
     * Получить контакты пользователя по определенному типу.
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    public function getContactsByType(User $user, array $data): array
    {
        $contactsQuery = $user->contacts();
        $contactsPaginator = $contactsQuery
            ->where('type', $data['type'])
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

    public function getProgress(User $user): array
    {
        return $this->getUserProgress($user->id);
    }
}
