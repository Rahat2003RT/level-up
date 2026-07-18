<?php

namespace Database\Factories;

use App\Models\PlanPause;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PlanPauseFactory extends Factory
{
    protected $model = PlanPause::class;

    public function definition(): array
    {
        return [
            'started_at' => Carbon::now(),
            'ended_at'   => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
