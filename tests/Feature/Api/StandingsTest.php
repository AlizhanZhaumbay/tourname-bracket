<?php

namespace Tests\Feature\Api;

use App\Http\Resources\StandingResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsTournaments;
use Tests\TestCase;

class StandingsTest extends TestCase
{
    use BuildsTournaments;
    use RefreshDatabase;

    #[Test]
    public function it_calculates_standings_for_a_completed_tournament(): void
    {
        $tournament = $this->draftTournamentWithTeams(4, maxTeams: 4);
        $teams = $tournament->teams()->orderBy('tournament_team.id')->get();
        $this->generateBracket($tournament);

        $this->putJson("/api/matches/{$this->matchAt($tournament, 1, 1)->id}/result", ['team1_score' => 2, 'team2_score' => 0]);
        $this->putJson("/api/matches/{$this->matchAt($tournament, 1, 2)->id}/result", ['team1_score' => 1, 'team2_score' => 2]);
        $this->putJson("/api/matches/{$this->matchAt($tournament, 2, 1)->id}/result", ['team1_score' => 3, 'team2_score' => 1]);

        $response = $this->getJson("/api/tournaments/{$tournament->id}/standings")
            ->assertOk()
            ->assertJsonStructure(['data' => ['*' => StandingResource::JSON_STRUCTURE]])
            ->assertJsonCount(4, 'data');

        $rows = collect($response->json('data'))
            ->map(fn (array $row) => [
                $row['team']['id'], $row['placement'], $row['eliminated'], $row['played'], $row['wins'],
                $row['losses'], $row['score_for'], $row['score_against'], $row['score_diff'],
            ])
            ->all();

        $this->assertSame([
            [$teams[0]->id, 1, false, 2, 2, 0, 5, 1, 4],
            [$teams[2]->id, 2, true, 2, 1, 1, 3, 4, -1],
            [$teams[1]->id, '3-4', true, 1, 0, 1, 1, 2, -1],
            [$teams[3]->id, '3-4', true, 1, 0, 1, 0, 2, -2],
        ], $rows);
    }

    #[Test]
    public function it_uses_placement_ranges_for_larger_brackets(): void
    {
        $tournament = $this->draftTournamentWithTeams(8);
        $this->generateBracket($tournament);
        $this->playAllReadyMatches($tournament);

        $placements = collect($this->getJson("/api/tournaments/{$tournament->id}/standings")->json('data'))
            ->pluck('placement')
            ->all();

        $this->assertSame([1, 2, '3-4', '3-4', '5-8', '5-8', '5-8', '5-8'], $placements);
    }

    #[Test]
    public function byes_are_not_counted_and_active_teams_have_no_placement(): void
    {
        $tournament = $this->draftTournamentWithTeams(6);
        $this->generateBracket($tournament);
        $match = $this->matchAt($tournament, 1, 2);
        $this->putJson("/api/matches/{$match->id}/result", ['team1_score' => 1, 'team2_score' => 0])->assertOk();

        $rows = collect($this->getJson("/api/tournaments/{$tournament->id}/standings")->json('data'))
            ->keyBy('team.id');

        $topSeed = $this->matchAt($tournament, 1, 1)->team1_id;
        $this->assertNull($rows[$topSeed]['placement']);
        $this->assertFalse($rows[$topSeed]['eliminated']);
        $this->assertSame(0, $rows[$topSeed]['played']);

        $this->assertSame('5-8', $rows[$match->team2_id]['placement']);
        $this->assertTrue($rows[$match->team2_id]['eliminated']);
        $this->assertSame($match->team2_id, $rows->last()['team']['id']);
    }
}
