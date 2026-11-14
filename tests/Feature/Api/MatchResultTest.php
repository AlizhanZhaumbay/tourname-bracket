<?php

namespace Tests\Feature\Api;

use App\Enums\MatchStatus;
use App\Enums\TournamentStatus;
use App\Http\Resources\MatchResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsTournaments;
use Tests\TestCase;

class MatchResultTest extends TestCase
{
    use BuildsTournaments;
    use RefreshDatabase;

    #[Test]
    public function it_records_result_and_advances_the_winner(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $semi1 = $this->matchAt($tournament, 1, 1);
        $semi2 = $this->matchAt($tournament, 1, 2);

        $this->putJson("/api/matches/{$semi1->id}/result", ['team1_score' => 1, 'team2_score' => 2])
            ->assertOk()
            ->assertJsonStructure(['data' => MatchResource::JSON_STRUCTURE])
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.winner_id', $semi1->team2_id);

        $final = $this->matchAt($tournament, 2, 1);
        $this->assertSame($semi1->team2_id, $final->team1_id);
        $this->assertSame(MatchStatus::Pending, $final->status);

        $this->putJson("/api/matches/{$semi2->id}/result", ['team1_score' => 3, 'team2_score' => 0])->assertOk();

        $final->refresh();
        $this->assertSame($semi2->team1_id, $final->team2_id);
        $this->assertSame(MatchStatus::Ready, $final->status);
    }

    #[Test]
    public function playing_the_final_completes_the_tournament(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $this->playAllReadyMatches($tournament);

        $final = $this->matchAt($tournament, 2, 1);
        $tournament->refresh();

        $this->assertSame(MatchStatus::Completed, $final->status);
        $this->assertSame(TournamentStatus::Completed, $tournament->status);
        $this->assertSame($final->winner_id, $tournament->champion_team_id);

        $this->getJson("/api/tournaments/{$tournament->id}")
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.champion.id', $final->winner_id);
    }

    #[Test]
    public function it_rejects_a_draw(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $match = $this->matchAt($tournament, 1, 1);

        $this->putJson("/api/matches/{$match->id}/result", ['team1_score' => 2, 'team2_score' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('team2_score')
            ->assertJsonMissingValidationErrors('team1_score');

        $this->assertSame(MatchStatus::Ready, $match->refresh()->status);
    }

    #[Test]
    public function it_validates_scores(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $match = $this->matchAt($tournament, 1, 1);

        $this->putJson("/api/matches/{$match->id}/result", ['team1_score' => -1, 'team2_score' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['team1_score', 'team2_score']);

        $this->putJson("/api/matches/{$match->id}/result", ['team1_score' => 1000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['team1_score', 'team2_score']);
    }

    #[Test]
    public function it_rejects_results_for_pending_and_bye_matches(): void
    {
        $tournament = $this->draftTournamentWithTeams(6);
        $this->generateBracket($tournament);
        $bye = $this->matchAt($tournament, 1, 1);
        $pending = $this->matchAt($tournament, 3, 1);

        $this->putJson("/api/matches/{$bye->id}/result", ['team1_score' => 1, 'team2_score' => 0])
            ->assertConflict()
            ->assertJsonPath('message', 'Cannot record a result for a bye match.');

        $this->putJson("/api/matches/{$pending->id}/result", ['team1_score' => 1, 'team2_score' => 0])
            ->assertConflict();
    }

    #[Test]
    public function it_corrects_a_result_while_next_match_is_unplayed(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $semi = $this->matchAt($tournament, 1, 1);

        $this->putJson("/api/matches/{$semi->id}/result", ['team1_score' => 2, 'team2_score' => 0])->assertOk();
        $this->assertSame($semi->team1_id, $this->matchAt($tournament, 2, 1)->team1_id);

        $this->putJson("/api/matches/{$semi->id}/result", ['team1_score' => 0, 'team2_score' => 2])
            ->assertOk()
            ->assertJsonPath('data.winner_id', $semi->team2_id)
            ->assertJsonPath('data.team1_score', 0);

        $final = $this->matchAt($tournament, 2, 1);
        $this->assertSame($semi->team2_id, $final->team1_id);
        $this->assertNull($final->team2_id);
    }

    #[Test]
    public function it_refuses_correction_after_next_match_is_played(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $this->playAllReadyMatches($tournament);
        $semi = $this->matchAt($tournament, 1, 1);

        $this->putJson("/api/matches/{$semi->id}/result", ['team1_score' => 0, 'team2_score' => 5])
            ->assertConflict()
            ->assertJsonPath('message', 'Cannot change the result: the next match has already been played.');

        $this->assertSame(2, $semi->refresh()->team1_score);
    }

    #[Test]
    public function correcting_the_final_updates_the_champion(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $this->playAllReadyMatches($tournament);
        $final = $this->matchAt($tournament, 2, 1);

        $this->putJson("/api/matches/{$final->id}/result", ['team1_score' => 0, 'team2_score' => 3])->assertOk();

        $tournament->refresh();
        $this->assertSame($final->team2_id, $tournament->champion_team_id);
        $this->assertSame(TournamentStatus::Completed, $tournament->status);
    }

    #[Test]
    public function it_shows_a_match_with_teams_and_next_match(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $semi = $this->matchAt($tournament, 1, 2);
        $final = $this->matchAt($tournament, 2, 1);

        $this->getJson("/api/matches/{$semi->id}")
            ->assertOk()
            ->assertJsonPath('data.team1.id', $semi->team1_id)
            ->assertJsonPath('data.next_match.id', $final->id)
            ->assertJsonPath('data.next_match_slot', 2);
    }

    #[Test]
    public function it_updates_only_scheduled_at(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $match = $this->matchAt($tournament, 1, 1);

        $this->patchJson("/api/matches/{$match->id}", ['scheduled_at' => '2026-12-01T18:00:00Z', 'team1_score' => 9])
            ->assertOk()
            ->assertJsonPath('data.scheduled_at', '2026-12-01T18:00:00.000000Z')
            ->assertJsonPath('data.team1_score', null);

        $this->patchJson("/api/matches/{$match->id}", ['scheduled_at' => 'tomorrow-ish'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scheduled_at');
    }
}
