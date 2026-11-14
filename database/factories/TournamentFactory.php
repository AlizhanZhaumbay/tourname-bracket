<?php

namespace Database\Factories;

use App\Enums\TournamentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Cup '.fake()->year(),
            'game' => fake()->randomElement(['Counter-Strike 2', 'Dota 2', 'Valorant', 'League of Legends']),
            'max_teams' => 8,
            'status' => TournamentStatus::Draft,
            'starts_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
        ];
    }

    public function maxTeams(int $maxTeams): static
    {
        return $this->state(['max_teams' => $maxTeams]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => TournamentStatus::InProgress]);
    }

    public function completed(): static
    {
        return $this->state(['status' => TournamentStatus::Completed]);
    }
}
