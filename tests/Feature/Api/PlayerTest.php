<?php

namespace Tests\Feature\Api;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_filters_players_by_team_and_free_agent(): void
    {
        $team = Team::factory()->withPlayers(2)->create();
        Team::factory()->withPlayers(1)->create();
        Player::factory()->freeAgent()->count(3)->create();

        $this->getJson("/api/players?team_id={$team->id}")->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/players?free_agent=true')->assertOk()->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.is_free_agent', true);
        $this->getJson('/api/players')->assertOk()->assertJsonPath('meta.total', 6);
    }

    #[Test]
    public function it_shows_a_player_with_team(): void
    {
        $player = Player::factory()->create();

        $this->getJson("/api/players/{$player->id}")
            ->assertOk()
            ->assertJsonPath('data.team.id', $player->team_id);
    }

    #[Test]
    public function it_transfers_a_player_and_releases_to_free_agency(): void
    {
        $player = Player::factory()->create();
        $newTeam = Team::factory()->create();

        $this->patchJson("/api/players/{$player->id}", ['team_id' => $newTeam->id])
            ->assertOk()
            ->assertJsonPath('data.team.id', $newTeam->id);

        $this->patchJson("/api/players/{$player->id}", ['team_id' => null])
            ->assertOk()
            ->assertJsonPath('data.is_free_agent', true)
            ->assertJsonPath('data.team', null);

        $this->patchJson("/api/players/{$player->id}", ['team_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('team_id');
    }

    #[Test]
    public function it_deletes_a_player(): void
    {
        $player = Player::factory()->create();

        $this->deleteJson("/api/players/{$player->id}")->assertNoContent();

        $this->assertModelMissing($player);
    }
}
