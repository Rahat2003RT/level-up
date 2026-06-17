<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'plan_type', 'status', 'starts_at', 'expires_at', 'robokassa_recurring_id', 'auto_renew'])]
class Subscription extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasActiveSubscription(string $plan): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $allowedPlans = match ($plan) {
            'pro' => ['pro', 'max'],
            'max' => ['max'],
            default => [$plan],
        };

        return $this->subscriptions()
            ->whereIn('plan_type', $allowedPlans)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now())
            ->exists();
    }
}
