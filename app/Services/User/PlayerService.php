<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\User;
use App\Models\DailyChecklist;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PlayerService
{
    /**
     * Получить существующий чек-лист или подготовить данные для нового.
     *
     * @param User $user
     * @param string $date
     * @return DailyChecklist|array|null
     */
    public function getOrCreateVirtual(User $user, string $date): DailyChecklist|array|null
    {
        $today = Carbon::today()->toDateString();
        $userId = $user->id;
        $checklist = DailyChecklist::where('user_id', $userId)
            ->where('date', $date)
            ->first();
        $progress = $this->getUserProgress($user->id);

        if ($checklist) {
            $checklist->progress = $progress;
            return $checklist;
        }

        if ($date === $today) {
            $nextDayNumber = DailyChecklist::where('user_id', $userId)->max('day_number') + 1;

            return [
                'date' => $date,
                'day_number' => $nextDayNumber,
                'is_completed' => false,
                'is_day_off'   => false,
                'progress'     => $progress,
            ];
        }

        return null;
    }

    /**
     * Создать или обновить чек-лист на сегодня.
     */
    public function storeOrUpdateToday(User $user, array $data): DailyChecklist
    {
        $checklist = $this->getTodayChecklist($user->id);

        if ($checklist && !$checklist->isEditable()) {
            throw new AuthorizationException('This checklist is no longer editable.');
        }

        $dayNumber = $checklist ? $checklist->day_number : $this->getNextDayNumber($user->id);

        $updatedChecklist = DailyChecklist::updateOrCreate(
            ['user_id' => $user->id, 'date' => Carbon::today()->toDateString()],
            array_merge($data, ['day_number' => $dayNumber])
        );

        $updatedChecklist->progress = $this->getUserProgress($user->id);
        return $updatedChecklist;
    }

    /**
     * Завершить чек-лист.
     */
    public function completeToday(User $user): DailyChecklist
    {
        $checklist = $this->getTodayChecklist($user->id);

        if (!$checklist) {
            throw new BadRequestHttpException('Checklist must be created before completion.');
        }

        if (!$checklist->isEditable()) {
            throw new AuthorizationException('This checklist is no longer editable.');
        }

        $checklist->update(['is_completed' => true]);

        $checklist->progress = $this->getUserProgress($user->id);
        return $checklist;
    }

    /**
     * Установить выходной день.
     */
    public function setDayOffToday(User $user): DailyChecklist
    {
        $defaultDayOffData = [
            'is_day_off' => true,
            'is_completed' => false,
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
        ];

        return $this->storeOrUpdateToday($user, $defaultDayOffData);
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
    public function getStatistics(User $user, array$data): array
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
                DB::raw('COUNT(CASE WHEN is_completed = 1 AND is_day_off = 0 THEN 1 END) as active_days_count')
            ])
            ->first();

        $totalMeetings = $totals->total_meetings ?? 0;
        $totalClients  = $totals->total_clients ?? 0;
        $totalPartners = $totals->total_partners ?? 0;
        $totalSales    = $totals->total_sales ?? 0;
        $totalIncome   = $totals->total_income ?? 0;
        $activeDays    = $totals->active_days_count ?? 0;

        $activeDaysPercentage = $days > 0 ? ($activeDays / $days) * 100 : 0;

        $totalVolume = $totalIncome;

        return [
            'period_days'            => $days,
            'total_meetings'         => $totalMeetings,
            'avg_meetings'           => $totalMeetings / $days,
            'total_clients'          => $totalClients,
            'avg_clients'            => $totalClients / $days,
            'total_partners'         => $totalPartners,
            'avg_partners'           => $totalPartners / $days,
            'total_sales'            => $totalSales,
            'avg_sales'              => $totalSales / $days,
            'total_income'           => $totalIncome,
            'avg_income'             => $totalIncome / $days,
            'active_days_count'      => $activeDays,
            'active_days_percentage' => $activeDaysPercentage,
            'total_volume'           => $totalVolume,
        ];
    }

    /**
     * Получить данные о прогрессе игрока (стрики, общие показатели).
     *
     * @param int $userId
     * @return array
     */
    public function getUserProgress(int $userId): array
    {
        $totalCompleted = DailyChecklist::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        $currentStreak = 0;

        $checklists = DailyChecklist::where('user_id', $userId)
            ->where('date', '<=', Carbon::today()->toDateString())
            ->orderBy('date', 'desc')
            ->get();

        foreach ($checklists as $index => $checklist) {
            if ($checklist->date === Carbon::today()->toDateString() && !$checklist->is_completed && !$checklist->is_day_off) {
                continue;
            }

            if ($checklist->is_completed || $checklist->is_day_off) {
                $currentStreak++;
            } else {
                break;
            }
        }

        return [
            'current_streak'  => $currentStreak,
            'total_completed' => $totalCompleted,
        ];
    }
}
