<?php

namespace Database\Factories;

use App\Models\LeadershipChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadershipChecklistFactory extends Factory
{
    protected $model = LeadershipChecklist::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => Carbon::today()->toDateString(),
            'day_number' => 1,
            'is_completed' => true,
            'is_day_off' => false,
            'checked_team_activity' => true,
            'contacted_players' => true,
            'added_new_player' => false,
            'held_online_meeting' => true,
            'posted_engaged_social_media' => true,
            'attracted_new_client' => false,
            'brought_new_partner' => false,
            'sent_new_invitations' => true,
        ];
    }
}
