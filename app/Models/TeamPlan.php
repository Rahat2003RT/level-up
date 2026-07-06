<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TeamPlan
 * @property int $id
 * @property int $user_id
 * @property int $daily_calls
 * @property int $daily_meetings
 * @property int $business_conversations
 * @property int $presentations
 * @property int $social_media_posts
 * @property int $new_clients_per_week
 * @property int $new_partners_per_week
 * @property int $daily_volume_points
 * @mixin Builder
 * @property-read User $leader
 */
class TeamPlan extends Model
{
    protected $fillable = [
        'user_id',
        'daily_calls',
        'daily_meetings',
        'business_conversations',
        'presentations',
        'social_media_posts',
        'new_clients_per_week',
        'new_partners_per_week',
        'daily_volume_points',
    ];

    protected $casts = [
        'user_id'                => 'integer',
        'daily_calls'            => 'integer',
        'daily_meetings'         => 'integer',
        'business_conversations' => 'integer',
        'presentations'          => 'integer',
        'social_media_posts'     => 'integer',
        'new_clients_per_week'   => 'integer',
        'new_partners_per_week'  => 'integer',
        'daily_volume_points'    => 'integer',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
