<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // автоматически создаст юзера, если не передан
            'name'    => $this->faker->name(),
            'phone'   => $this->faker->phoneNumber(),
            'type'    => $this->faker->randomElement(['client', 'partner']),
            'volume'  => $this->faker->numberBetween(10, 500),
        ];
    }
}
