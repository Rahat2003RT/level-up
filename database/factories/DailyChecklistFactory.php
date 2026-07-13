<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DailyChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyChecklist>
 */
final class DailyChecklistFactory extends Factory
{
    protected $model = DailyChecklist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => $this->faker->date(),
            'day_number' => $this->faker->numberBetween(1, 90),
            'is_completed' => $this->faker->boolean(),
            'is_day_off' => $this->faker->boolean(),
            'scheduled_meetings' => $this->faker->numberBetween(0, 5),
            'completed_meetings' => $this->faker->numberBetween(0, 5),
            'new_clients' => $this->faker->numberBetween(0, 3),
            'new_partners' => $this->faker->numberBetween(0, 2),
            'business_conversations' => $this->faker->numberBetween(0, 10),
            'presentations' => $this->faker->numberBetween(0, 5),
            'sales' => $this->faker->numberBetween(0, 5),
            'daily_income' => $this->faker->randomFloat(2, 0, 1000),
            'social_media_activity' => $this->faker->boolean(),
            'communication_with_sponsor' => $this->faker->boolean(),
            'plans_for_the_day' => $this->faker->sentence(),
            'results_for_the_day' => $this->faker->sentence(),
            'notes_for_the_day' => $this->faker->sentence(),
        ];
    }
}
