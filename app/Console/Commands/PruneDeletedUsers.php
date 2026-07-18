<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PruneDeletedUsers extends Command
{
    protected $signature = 'users:prune-deleted';
    protected $description = 'Физически удаляет пользователей, которые были мягко удалены более 30 дней назад';

    public function handle(): int
    {
        // Ищем пользователей с deleted_at старше 30 дней и стираем насовсем
        $deletedCount = User::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(30))
            ->forceDelete();

        $this->info("Успешно очищено пользователей: {$deletedCount}");

        return Command::SUCCESS;
    }
}
