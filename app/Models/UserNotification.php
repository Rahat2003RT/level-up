<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class UserNotification
 *
 * @property int $id
 * @property int $user_id
 * @property array $title
 * @property array|null $description
 * @property bool $is_read
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User $user
 *
 * @method static Builder|UserNotification query()
 * @method static Builder|UserNotification newQuery()
 * @method static Builder|UserNotification newModelQuery()
 * @method static Builder|UserNotification where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static UserNotification create(array $attributes = [])
 */
class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'title'       => 'array',
        'description' => 'array',
        'is_read'     => 'boolean',
        'read_at'     => 'datetime',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
