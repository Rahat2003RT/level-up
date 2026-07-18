<?php

namespace App\Services\User;

use App\Enums\Period;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TariffService
{
    /**
     * Получить список активных тарифов для конкретной роли.
     *
     * @param User $user
     * @return Collection
     */
    public function getTariffs(User $user): Collection
    {
        $userRole = $user->role?->value;
        if (!$userRole) {
            return new Collection();
        }

        return Tariff::query()
            ->where('is_active', true)
            ->where('role', $userRole)
            ->orderBy('price', 'asc')
            ->get();
    }

    /**
     * Логика выбора тарифа (имитация подписки).
     *
     * @throws ValidationException
     */
    public function selectTariff(User $user, int $tariffId): void
    {
        /** @var Tariff $tariff */
        $tariff = Tariff::query()
            ->where('is_active', true)
            ->where('id', $tariffId)
            ->first();

        if (!$tariff || $tariff->role !== $user->role?->value) {
            throw ValidationException::withMessages([
                'tariff' => ['The selected tariff plan is invalid or unavailable for your role.']
            ]);
        }

        $periodEnum = $tariff->period;

        $subscriptionEndsAt = match ($periodEnum) {
            Period::Weak => now()->addWeek(),
            Period::Month => now()->addMonth(),
            Period::ThreeMonths => now()->addMonths(3),
            Period::FourMonths => now()->addMonths(4),
            Period::SixMonths => now()->addMonths(6),
            Period::Year => now()->addYear(),
        };

        $user->update([
            'tariff_id'            => $tariff->id,
            'subscription_ends_at' => $subscriptionEndsAt,
            'auto_renew'           => true,
        ]);
    }

    /**
     * Отмена продления тарифа.
     *
     * @throws ValidationException
     */
    public function cancelAutoRenew(User $user): void
    {
        if (!$user->tariff_id) {
            throw ValidationException::withMessages([
                'subscription' => ['You do not have an active tariff plan to cancel.']
            ]);
        }
        $user->update([
            'auto_renew' => false
        ]);
    }
}
