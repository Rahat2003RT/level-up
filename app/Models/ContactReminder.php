<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contact_id', 'description', 'reminder_at', 'is_notified'])]
class ContactReminder extends Model
{
    protected function casts(): array
    {
        return [
            'reminder_at' => 'datetime',
            'is_notified' => 'boolean',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
