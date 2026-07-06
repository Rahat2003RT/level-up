<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LeadershipChecklist
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property int $day_number
 * @property bool $is_completed
 * @property bool $is_day_off
 * @property bool $checked_team_activity
 * @property bool $contacted_players
 * @property bool $added_new_player
 * @property bool $held_online_meeting
 * @property bool $posted_engaged_social_media
 * @property bool $attracted_new_client
 * @property bool $brought_new_partner
 * @property bool $sent_new_invitations
 * @property string|null $notes_for_the_day
 * @mixin Builder
 * @property-read User $user
 * @property bool $is_editable
 */
class LeadershipChecklist extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'day_number',
        'is_completed',
        'is_day_off',
        'checked_team_activity',
        'contacted_players',
        'added_new_player',
        'held_online_meeting',
        'posted_engaged_social_media',
        'attracted_new_client',
        'brought_new_partner',
        'sent_new_invitations',
        'notes_for_the_day',
    ];

    protected $casts = [
        'date' => 'date',
        'day_number' => 'integer',
        'is_completed' => 'boolean',
        'is_day_off' => 'boolean',
        'checked_team_activity' => 'boolean',
        'contacted_players' => 'boolean',
        'added_new_player' => 'boolean',
        'held_online_meeting' => 'boolean',
        'posted_engaged_social_media' => 'boolean',
        'attracted_new_client' => 'boolean',
        'brought_new_partner' => 'boolean',
        'sent_new_invitations' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isEditable(): bool
    {
        return !$this->is_completed && !$this->is_day_off && $this->date->isToday();
    }
}
