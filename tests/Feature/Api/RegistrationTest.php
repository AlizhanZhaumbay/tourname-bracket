<?php

namespace Tests\Feature\Api;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsTournaments;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use BuildsTournaments;
    use RefreshDatabase;

    #[Test]
    public function it_registers_a_team_with_a_seed(): void
    {
        $tournament = Tournament::factory()->create();
        $team = Team::factory()->withPlayers(1)->create();

        $this->postJson("/api/tournaments/{$tournament->id}/teams", ['team_id' => $team->id, 'seed' => 3])
            ->assertCreated()
            ->assertHeader('Location', route('tournaments.teams.index', $tournament))
            ->assertJsonPath('data.id', $team->id)
            ->assertJsonPath('data.seed', 3);

        $this->getJson("/api/tournaments/{$tournament->id}/teams")
            ->assertOk()
            ->assertJsonPath('data.0.seed', 3);
    }

    #[Test]
    public function it_rejects_registration_after_bracket_generation(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $team = Team::factory()->withPlayers(1)->create();

        $this->postJson("/api/tournaments/{$tournament->id}/teams", ['team_id' => $team->id])
            ->assertConflict()
            ->assertExactJson(['message' => 'Registration is closed: bracket already generated.']);
    }

    #[Test]
    public function it_rejects_duplicate_registration(): void
    {
        $tournament = Tournament::factory()->create();
        $team = $this->registeredTeams($tournament, 1)->first();

        $this->postJson("/api/tournaments/{$tournament->id}/teams", ['team_id' => $team->id])
            ->assertConflict()
            ->assertJsonPath('message', 'Team is already registered in this tournament.');
    }

    #[Test]
    public function it_rejects_registration_when_tournament_is_full(): void
    {
        $tournament = $this->draftTournamentWithTeams(4, maxTeams: 4);
        $team = Team::factory()->withPlayers(1)->create();

        $this->postJson("/api/tournaments/{$tournament->id}/teams", ['team_id' => $team->id])
            ->assertConflict()
            ->assertJsonPath('message', 'Tournament is full: maximum of 4 teams reached.');
    }

    #[Test]
    public function it_requires_team_to_have_players(): void
    {
        $tournament = Tournament::factory()->create();
        $team = Team::factory()->create();

        $this->postJson("/api/tournaments/{$tournament->id}/teams", ['team_id' => $team->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('team_id');
    }

    #[Test]
    public function it_validates_seed_range_and_uniqueness(): void
    {
        $tournament = Tournament::factory()->maxTeams(4)->create();
        $taken = $this->registeredTeams($tournament, 1)->first();
        $tournament->teams()->updateExistingPivot($taken->id, ['seed' => 2]);
        $team = Team::factory()->withPlayers(1)->create();

        $this->postJson("/api/tournaments/{$tournament->id}/teams", ['team_id' => $team->id, 'seed' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seed');

        $this->postJson("/api/tournaments/{$tournament->id}/teams", ['team_id' => $team->id, 'seed' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seed');

        $this->postJson("/api/tournaments/{$tournament->id}/teams", ['team_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('team_id');
    }

    #[Test]
    public function it_withdraws_a_team_only_in_draft(): void
    {
        $tournament = $this->draftTournamentWithTeams(3);
        [$first, $second] = $tournament->teams;

        $this->deleteJson("/api/tournaments/{$tournament->id}/teams/{$first->id}")->assertNoContent();
        $this->deleteJson("/api/tournaments/{$tournament->id}/teams/{$first->id}")->assertNotFound();

        $this->generateBracket($tournament);

        $this->deleteJson("/api/tournaments/{$tournament->id}/teams/{$second->id}")->assertConflict();
    }
}
