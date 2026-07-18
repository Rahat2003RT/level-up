<?php

namespace App\Listeners;

use App\Events\TariffUpdated;
use App\Models\User;

class CancelSubscriptionsOnTariffChange
{
    /**
     * Handle the event.
     */
    public function handle(TariffUpdated $event): void
    {
        $tariff = $event->tariff;
        User::where('tariff_id', $tariff->id)->update([
            'auto_renew' => false
        ]);
    }
}
