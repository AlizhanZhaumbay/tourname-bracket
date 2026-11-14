<?php

namespace Tests\Concerns;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;

trait BuildsTournaments
{
    protected function draftTournamentWithTeams(int $teams, int $maxTeams = 8): Tournament
    {
        $tournament = Tournament::factory()->maxTeams($maxTeams)->create();

        $this->registeredTeams($tournament, $teams);

        return $tournament;
    }

    protected function registeredTeams(Tournament $tournament, int $count): Collection
    {
        $teams = Team::factory()->withPlayers(1)->count($count)->create();

        foreach ($teams as $team) {
            $tournament->teams()->attach($team->id);
        }

        return $teams;
    }

    protected function generateBracket(Tournament $tournament, array $body = []): void
    {
        $this->postJson("/api/tournaments/{$tournament->id}/bracket", $body)->assertCreated();
    }

    protected function matchAt(Tournament $tournament, int $round, int $position): GameMatch
    {
        return $tournament->matches()->where('round', $round)->where('position', $position)->firstOrFail();
    }

    protected function seedOf(Tournament $tournament, ?int $teamId): ?int
    {
        return $tournament->teams()->whereKey($teamId)->first()?->pivot->seed;
    }

    protected function playAllReadyMatches(Tournament $tournament): void
    {
        while ($match = $tournament->matches()->where('status', MatchStatus::Ready)->orderBy('round')->orderBy('position')->first()) {
            $this->putJson("/api/matches/{$match->id}/result", ['team1_score' => 2, 'team2_score' => 1])->assertOk();
        }
    }
}
