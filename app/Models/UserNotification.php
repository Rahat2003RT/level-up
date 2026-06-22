<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class UserNotification
 * * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string|null $image
 * @property bool $is_read
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * * @property-read User $user
 * @method static insert(array $dbPayload)
 */
class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'image',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
