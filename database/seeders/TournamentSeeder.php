<?php

namespace Database\Seeders;

use App\Enums\MatchStatus;
use App\Enums\SeedingMethod;
use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\BracketGenerator;
use App\Services\MatchResultService;
use App\Services\RegistrationService;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    public function __construct(
        private readonly RegistrationService $registrations,
        private readonly BracketGenerator $brackets,
        private readonly MatchResultService $results,
    ) {}

    public function run(): void
    {
        $teams = Team::query()->orderBy('id')->get();

        $draft = Tournament::factory()->maxTeams(8)->create([
            'name' => 'Winter Open 2026',
            'game' => 'Counter-Strike 2',
        ]);
        $this->register($draft, $teams->slice(0, 6));

        $ongoing = Tournament::factory()->maxTeams(8)->create([
            'name' => 'Autumn Masters 2026',
            'game' => 'Dota 2',
            'starts_at' => now()->subWeek(),
        ]);
        $this->register($ongoing, $teams->slice(6, 8));
        $this->brackets->generateBracket($ongoing, SeedingMethod::RegistrationOrder);
        $this->playRound($ongoing, 1);

        $finished = Tournament::factory()->maxTeams(4)->create([
            'name' => 'Summer Cup 2026',
            'game' => 'Valorant',
            'starts_at' => now()->subMonths(3),
        ]);
        $this->register($finished, $teams->slice(12, 4));
        $this->brackets->generateBracket($finished, SeedingMethod::RegistrationOrder);
        $this->playRound($finished, 1);
        $this->playRound($finished, 2);
    }

    private function register(Tournament $tournament, iterable $teams): void
    {
        foreach ($teams as $team) {
            $this->registrations->registerTeam($tournament, $team, null);
        }
    }

    private function playRound(Tournament $tournament, int $round): void
    {
        $tournament->matches()
            ->where('round', $round)
            ->where('status', MatchStatus::Ready)
            ->orderBy('position')
            ->get()
            ->each(function (GameMatch $match) {
                $winnerScore = fake()->numberBetween(13, 16);
                $loserScore = fake()->numberBetween(3, 12);

                [$team1Score, $team2Score] = fake()->boolean(65) ? [$winnerScore, $loserScore] : [$loserScore, $winnerScore];

                $this->results->recordResult($match, $team1Score, $team2Score);
            });
    }
}
