<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'activity_cycle_id', 'calendar_date', 'day_number', 'status',
    'contacts_count', 'presentations_count', 'sales_volume',
    'partners_count', 'team_meetings_count', 'self_education_minutes'
])]
class DailyReport extends Model
{
    protected function casts(): array
    {
        return [
            'calendar_date' => 'date',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ActivityCycle::class, 'activity_cycle_id');
    }
}
