<?php

namespace App\Models;

use App\Enums\ContactType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Contact
 * @package App\Models
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $volume
 * @property string|null $comment
 * @property string|null $date_of_birth
 * @property string|null $type
 * @property Carbon|null $reminder_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 */
class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'volume',
        'comment',
        'date_of_birth',
        'type',
        'reminder_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'reminder_at' => 'datetime',
            'type' => ContactType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
