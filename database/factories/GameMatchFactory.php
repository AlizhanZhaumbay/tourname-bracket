<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory()->inProgress(),
            'round' => 1,
            'position' => 1,
            'team1_id' => Team::factory(),
            'team2_id' => Team::factory(),
            'status' => MatchStatus::Ready,
        ];
    }
}
