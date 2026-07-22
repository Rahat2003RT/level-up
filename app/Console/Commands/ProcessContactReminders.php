<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendContactReminderNotification;
use App\Models\Contact;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class ProcessContactReminders extends Command
{
    protected $signature = 'reminders:process-contacts';
    protected $description = 'Process and send contact reminders considering user timezone';

    public function handle(): void
    {
        // Берем контакты пачками, у которых есть дата напоминания
        Contact::with('user')
            ->whereNotNull('reminder_at')
            ->chunkById(200, function ($contacts) {
                foreach ($contacts as $contact) {
                    $user = $contact->user;

                    if (!$user || !$user->notifications_enabled) {
                        continue;
                    }

                    // Если timezone не задана, берём серверное время приложения
                    $tz = $user->timezone ?: config('app.timezone');

                    // Текущее время в часовом поясе пользователя
                    $nowInUserTz = now()->setTimezone($tz)->startOfMinute();

                    // Время напоминания (считаем, что оно сохранено в его локальном времени)
                    // Конвертируем строку из базы в Carbon-объект в часовом поясе пользователя
                    $reminderTime = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $contact->reminder_at->format('Y-m-d H:i:s'),
                        $tz
                    )->startOfMinute();

                    // Если время напоминания настало (или уже прошло)
                    if ($reminderTime->lessThanOrEqualTo($nowInUserTz)) {
                        // Отправляем пуш
                        SendContactReminderNotification::dispatch($contact);

                        // Очищаем reminder_at, чтобы больше не отправлять по этому контакту
                        $contact->update(['reminder_at' => null]);
                    }
                }
            });
    }
}
