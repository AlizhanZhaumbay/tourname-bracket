<?php

namespace Tests\Feature\Api;

use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsTournaments;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use BuildsTournaments;
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_tournament_always_in_draft(): void
    {
        $response = $this->postJson('/api/tournaments', [
            'name' => 'Spring Cup',
            'game' => 'Dota 2',
            'max_teams' => 16,
            'status' => 'completed',
        ])
            ->assertCreated()
            ->assertJsonStructure(['data' => TournamentResource::JSON_STRUCTURE])
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.teams_count', 0);

        $response->assertHeader('Location', route('tournaments.show', $response->json('data.id')));
    }

    #[Test]
    public function it_validates_max_teams(): void
    {
        $this->postJson('/api/tournaments', ['name' => 'Cup', 'game' => 'CS2', 'max_teams' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_teams');
    }

    #[Test]
    public function it_filters_tournaments(): void
    {
        Tournament::factory()->create(['game' => 'Dota 2']);
        Tournament::factory()->inProgress()->create(['game' => 'Dota 2']);
        Tournament::factory()->completed()->create(['game' => 'Valorant']);

        $this->getJson('/api/tournaments?status=in_progress')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/tournaments?game=dota 2')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/tournaments?status=unknown')->assertUnprocessable();
    }

    #[Test]
    public function it_shows_teams_count_and_champion(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);

        $this->getJson("/api/tournaments/{$tournament->id}")
            ->assertOk()
            ->assertJsonPath('data.teams_count', 4)
            ->assertJsonPath('data.champion', null);
    }

    #[Test]
    public function max_teams_cannot_drop_below_registered_teams(): void
    {
        $tournament = $this->draftTournamentWithTeams(5, maxTeams: 8);

        $this->patchJson("/api/tournaments/{$tournament->id}", ['max_teams' => 4])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_teams');

        $this->patchJson("/api/tournaments/{$tournament->id}", ['max_teams' => 16, 'name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.max_teams', 16)
            ->assertJsonPath('data.name', 'Renamed');
    }

    #[Test]
    public function max_teams_can_change_only_in_draft(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);

        $this->patchJson("/api/tournaments/{$tournament->id}", ['max_teams' => 16])->assertConflict();
        $this->patchJson("/api/tournaments/{$tournament->id}", ['name' => 'Renamed'])->assertOk();
    }

    #[Test]
    public function it_deletes_draft_and_completed_tournaments_with_cascade(): void
    {
        $draft = $this->draftTournamentWithTeams(2);
        $completed = $this->draftTournamentWithTeams(2);
        $this->generateBracket($completed);
        $this->playAllReadyMatches($completed);

        $this->deleteJson("/api/tournaments/{$draft->id}")->assertNoContent();
        $this->deleteJson("/api/tournaments/{$completed->id}")->assertNoContent();

        $this->assertDatabaseCount('tournament_team', 0);
        $this->assertDatabaseCount('matches', 0);
    }

    #[Test]
    public function it_refuses_to_delete_tournament_in_progress(): void
    {
        $tournament = $this->draftTournamentWithTeams(2);
        $this->generateBracket($tournament);

        $this->deleteJson("/api/tournaments/{$tournament->id}")->assertConflict();
        $this->assertModelExists($tournament);
    }
}
