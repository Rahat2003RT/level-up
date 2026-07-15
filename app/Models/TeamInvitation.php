<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Class TeamInvitation
 *
 * @property int $id
 * @property int $leader_id
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $leader
 *
 * @method static Builder|TeamInvitation query()
 * @method static Builder|TeamInvitation newModelQuery()
 * @method static Builder|TeamInvitation newQuery()
 * @method static TeamInvitation updateOrCreate(array $attributes, array $values = [])
 * @method static TeamInvitation create(array $attributes = [])
 * @method static Builder|TeamInvitation whereLeaderId($value)
 * @method static Builder|TeamInvitation whereToken($value)
 */
class TeamInvitation extends Model
{
    use HasFactory;
    protected $fillable = [
        'leader_id',
        'token',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
