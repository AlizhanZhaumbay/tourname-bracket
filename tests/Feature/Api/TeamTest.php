<?php

namespace Tests\Feature\Api;

use App\Http\Resources\PlayerResource;
use App\Http\Resources\TeamResource;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsTournaments;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use BuildsTournaments;
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_team_and_normalizes_tag(): void
    {
        $response = $this->postJson('/api/teams', ['name' => 'Iron Lions', 'tag' => 'irln', 'country' => 'gb'])
            ->assertCreated()
            ->assertJsonStructure(['data' => TeamResource::JSON_STRUCTURE])
            ->assertJsonPath('data.tag', 'IRLN')
            ->assertJsonPath('data.country', 'GB');

        $response->assertHeader('Location', route('teams.show', $response->json('data.id')));
    }

    #[Test]
    public function it_validates_team_fields(): void
    {
        Team::factory()->create(['name' => 'Taken', 'tag' => 'TKN']);

        $this->postJson('/api/teams', ['name' => 'Taken', 'tag' => 'T-K!', 'country' => 'USA'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'tag', 'country']);

        $this->postJson('/api/teams', ['name' => 'Other', 'tag' => 'tkn'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tag');
    }

    #[Test]
    public function it_lists_teams_with_search_and_country_filters(): void
    {
        Team::factory()->create(['name' => 'Northern Wolves', 'tag' => 'NWLV', 'country' => 'SE']);
        Team::factory()->create(['name' => 'Red Bears', 'tag' => 'RBRS', 'country' => 'CZ']);
        Team::factory()->create(['name' => 'Wolfpack', 'tag' => 'WOLF', 'country' => 'CZ']);

        $this->getJson('/api/teams?search=wol')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/teams?search=wol&country=cz')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tag', 'WOLF');
        $this->getJson('/api/teams?per_page=1')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['links' => ['first', 'next'], 'meta' => ['current_page', 'total']]);
    }

    #[Test]
    public function it_shows_a_team_with_players(): void
    {
        $team = Team::factory()->withPlayers(3)->create();

        $this->getJson("/api/teams/{$team->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data.players');
    }

    #[Test]
    public function it_partially_updates_a_team(): void
    {
        $team = Team::factory()->create(['name' => 'Old', 'tag' => 'OLD', 'country' => 'DE']);

        $this->patchJson("/api/teams/{$team->id}", ['name' => 'New'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New')
            ->assertJsonPath('data.tag', 'OLD')
            ->assertJsonPath('data.country', 'DE');

        $this->patchJson("/api/teams/{$team->id}", ['tag' => 'OLD'])->assertOk();
    }

    #[Test]
    public function it_deletes_a_team_and_frees_its_players(): void
    {
        $team = Team::factory()->withPlayers(2)->create();

        $this->deleteJson("/api/teams/{$team->id}")->assertNoContent();

        $this->assertModelMissing($team);
        $this->assertSame(2, Player::query()->whereNull('team_id')->count());
    }

    #[Test]
    public function it_refuses_to_delete_a_team_registered_in_active_tournament(): void
    {
        $tournament = $this->draftTournamentWithTeams(2);

        $this->deleteJson("/api/teams/{$tournament->teams->first()->id}")->assertConflict();
    }

    #[Test]
    public function it_deletes_a_team_from_completed_tournament(): void
    {
        $tournament = Tournament::factory()->completed()->create();
        $team = $this->registeredTeams($tournament, 1)->first();

        $this->deleteJson("/api/teams/{$team->id}")->assertNoContent();
    }

    #[Test]
    public function it_manages_team_players(): void
    {
        $team = Team::factory()->withPlayers(1)->create();

        $response = $this->postJson("/api/teams/{$team->id}/players", ['nickname' => 'ace', 'role' => 'Entry'])
            ->assertCreated()
            ->assertJsonStructure(['data' => PlayerResource::JSON_STRUCTURE])
            ->assertJsonPath('data.team_id', $team->id);

        $response->assertHeader('Location', route('players.show', $response->json('data.id')));

        $this->getJson("/api/teams/{$team->id}/players")->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_lists_team_match_history(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $this->playAllReadyMatches($tournament);
        $champion = $tournament->refresh()->champion_team_id;

        $this->getJson("/api/teams/{$champion}/matches")->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/teams/{$champion}/matches?tournament_id={$tournament->id}&status=completed")
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->getJson("/api/teams/{$champion}/matches?status=bye")->assertOk()->assertJsonCount(0, 'data');
    }
}
