<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'nickname' => fake()->unique()->userName(),
            'full_name' => fake()->name(),
            'role' => fake()->randomElement(['Captain', 'Entry', 'Support', 'AWPer', 'Lurker', 'Coach']),
        ];
    }

    public function freeAgent(): static
    {
        return $this->state(['team_id' => null]);
    }
}
