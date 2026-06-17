<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGoal extends Model
{
    protected $fillable = [
        'user_id',
        'target_clients_count',
        'target_partners_count',
        'target_sales_volume',
        'period'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
