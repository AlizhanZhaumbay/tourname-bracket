<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' '.fake()->randomElement(['Esports', 'Gaming', 'Club', 'Squad']),
            'tag' => strtoupper(fake()->unique()->bothify('??##')),
            'country' => fake()->countryCode(),
        ];
    }

    public function withPlayers(int $count = 5): static
    {
        return $this->has(Player::factory()->count($count));
    }
}
