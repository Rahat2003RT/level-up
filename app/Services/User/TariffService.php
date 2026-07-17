<?php

namespace App\Services\User;

use App\Models\Tariff;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

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
}
