<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\DailyChecklist;
use Carbon\Carbon;

class CloseMissingChecklistsCommand extends Command
{
    protected $signature = 'checklists:auto-close';
    protected $description = 'Автоматически закрывает незаполненные чек-листы пользователей в полночь по их локальному времени';

    public function handle(): void
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $userTimezone = $user->timezone ?? config('app.timezone');
                $userLocalTime = Carbon::now($userTimezone);

                if ($userLocalTime->hour === 0) {
                    $yesterdayString = $userLocalTime->subDay()->toDateString();

                    $checklist = DailyChecklist::where('user_id', $user->id)
                        ->where('date', $yesterdayString)
                        ->first();

                    if (!$checklist) {
                        $nextDayNumber = DailyChecklist::where('user_id', $user->id)->count() + 1;

                        DailyChecklist::create([
                            'user_id' => $user->id,
                            'date' => $yesterdayString,
                            'day_number' => $nextDayNumber,
                            'is_completed' => true,
                            'is_day_off' => false,
                        ]);
                    } elseif (!$checklist->is_completed && !$checklist->is_day_off) {
                        $checklist->update(['is_completed' => true]);
                    }
                }
            }
        });

        $this->info('Процесс автоматического закрытия чек-листов успешно завершен.');
    }
}
