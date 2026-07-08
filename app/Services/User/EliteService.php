<?php

namespace App\Services\User;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;
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


}
