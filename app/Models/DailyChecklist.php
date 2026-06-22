<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DailyChecklist
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property int $day_number
 * @property bool $is_completed
 * @property bool $is_day_off
 * @property int $scheduled_meetings
 * @property int $completed_meetings
 * @property int $new_clients
 * @property int $new_partners
 * @property int $business_conversations
 * @property int $presentations
 * @property int $sales
 * @property int $daily_income
 * @property bool $social_media_activity
 * @property bool $communication_with_sponsor
 * @property string|null $plans_for_the_day
 * @property string|null $results_for_the_day
 * @property string|null $notes_for_the_day
 *
 * @property-read User $user
 */
class DailyChecklist extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'day_number',
        'is_completed',
        'is_day_off',
        'scheduled_meetings',
        'completed_meetings',
        'new_clients',
        'new_partners',
        'business_conversations',
        'presentations',
        'sales',
        'daily_income',
        'social_media_activity',
        'communication_with_sponsor',
        'plans_for_the_day',
        'results_for_the_day',
        'notes_for_the_day',
    ];

    protected $casts = [
        'date' => 'date',
        'is_completed' => 'boolean',
        'is_day_off' => 'boolean',
        'social_media_activity' => 'boolean',
        'communication_with_sponsor' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
