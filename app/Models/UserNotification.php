<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification
{
    protected array $fillable = [
        'user_id',
        'title',
        'description',
        'image',
        'is_read',
        'read_at',
    ];

    protected array $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
}
