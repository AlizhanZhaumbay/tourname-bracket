<?php

namespace Tests\Feature\Api;

use App\Enums\MatchStatus;
use App\Enums\TournamentStatus;
use App\Http\Resources\BracketResource;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsTournaments;
use Tests\TestCase;

class BracketTest extends TestCase
{
    use BuildsTournaments;
    use RefreshDatabase;

    #[Test]
    public function it_generates_an_eight_team_bracket_with_standard_seed_pairs(): void
    {
        $tournament = $this->draftTournamentWithTeams(8);

        $this->postJson("/api/tournaments/{$tournament->id}/bracket", ['seeding' => 'registration_order'])
            ->assertCreated()
            ->assertHeader('Location', route('tournaments.bracket.show', $tournament))
            ->assertJsonStructure(['data' => BracketResource::JSON_STRUCTURE])
            ->assertJsonPath('data.size', 8)
            ->assertJsonPath('data.rounds.0.name', 'Quarterfinal')
            ->assertJsonPath('data.rounds.1.name', 'Semifinal')
            ->assertJsonPath('data.rounds.2.name', 'Final');

        $tournament->refresh();
        $this->assertSame(TournamentStatus::InProgress, $tournament->status);
        $this->assertSame(7, $tournament->matches()->count());

        $expectedPairs = [1 => [1, 8], 2 => [4, 5], 3 => [2, 7], 4 => [3, 6]];

        foreach ($expectedPairs as $position => [$seed1, $seed2]) {
            $match = $this->matchAt($tournament, 1, $position);

            $this->assertSame($seed1, $this->seedOf($tournament, $match->team1_id));
            $this->assertSame($seed2, $this->seedOf($tournament, $match->team2_id));
            $this->assertSame(MatchStatus::Ready, $match->status);
        }
    }

    #[Test]
    public function it_links_every_match_to_the_next_round(): void
    {
        $tournament = $this->draftTournamentWithTeams(8);
        $this->generateBracket($tournament);

        foreach ([1 => 4, 2 => 2] as $round => $count) {
            for ($position = 1; $position <= $count; $position++) {
                $match = $this->matchAt($tournament, $round, $position);
                $expectedNext = $this->matchAt($tournament, $round + 1, (int) ceil($position / 2));

                $this->assertSame($expectedNext->id, $match->next_match_id);
                $this->assertSame($position % 2 === 1 ? 1 : 2, $match->next_match_slot);
            }
        }

        $final = $this->matchAt($tournament, 3, 1);
        $this->assertNull($final->next_match_id);
        $this->assertNull($final->next_match_slot);
        $this->assertSame(MatchStatus::Pending, $final->status);
    }

    #[Test]
    public function it_gives_byes_to_top_seeds_in_a_six_team_bracket(): void
    {
        $tournament = $this->draftTournamentWithTeams(6);
        $this->generateBracket($tournament);

        $byes = $tournament->matches()->where('status', MatchStatus::Bye)->orderBy('position')->get();
        $this->assertCount(2, $byes);
        $this->assertSame([1, 3], $byes->pluck('position')->all());
        $this->assertSame([1, 2], $byes->map(fn ($m) => $this->seedOf($tournament, $m->team1_id))->all());
        $this->assertNull($byes[0]->team2_id);
        $this->assertSame($byes[0]->team1_id, $byes[0]->winner_id);

        $semi1 = $this->matchAt($tournament, 2, 1);
        $semi2 = $this->matchAt($tournament, 2, 2);
        $this->assertSame(1, $this->seedOf($tournament, $semi1->team1_id));
        $this->assertSame(2, $this->seedOf($tournament, $semi2->team1_id));
        $this->assertNull($semi1->team2_id);
        $this->assertSame(MatchStatus::Pending, $semi1->status);

        $this->assertSame(MatchStatus::Ready, $this->matchAt($tournament, 1, 2)->status);
        $this->assertSame(MatchStatus::Ready, $this->matchAt($tournament, 1, 4)->status);
    }

    #[Test]
    public function it_marks_second_round_match_ready_when_both_teams_get_byes(): void
    {
        $tournament = $this->draftTournamentWithTeams(5);
        $this->generateBracket($tournament);

        $this->assertSame(3, $tournament->matches()->where('status', MatchStatus::Bye)->count());
        $this->assertSame(MatchStatus::Ready, $this->matchAt($tournament, 2, 2)->status);
    }

    #[Test]
    public function it_uses_manual_seeds_when_they_are_complete(): void
    {
        $tournament = Tournament::factory()->maxTeams(4)->create();
        $teams = $this->registeredTeams($tournament, 4);
        foreach ([3, 1, 4, 2] as $index => $seed) {
            $tournament->teams()->updateExistingPivot($teams[$index]->id, ['seed' => $seed]);
        }

        $this->generateBracket($tournament, ['seeding' => 'manual']);

        $first = $this->matchAt($tournament, 1, 1);
        $this->assertSame($teams[1]->id, $first->team1_id);
        $this->assertSame($teams[2]->id, $first->team2_id);
    }

    #[Test]
    public function it_rejects_incomplete_manual_seeding(): void
    {
        $tournament = Tournament::factory()->maxTeams(4)->create();
        $teams = $this->registeredTeams($tournament, 3);
        $tournament->teams()->updateExistingPivot($teams[0]->id, ['seed' => 1]);
        $tournament->teams()->updateExistingPivot($teams[1]->id, ['seed' => 3]);

        $this->postJson("/api/tournaments/{$tournament->id}/bracket", ['seeding' => 'manual'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seeding');

        $this->assertSame(0, $tournament->matches()->count());
        $this->assertSame(TournamentStatus::Draft, $tournament->refresh()->status);
    }

    #[Test]
    public function random_seeding_assigns_a_full_seed_range(): void
    {
        $tournament = $this->draftTournamentWithTeams(7);

        $this->generateBracket($tournament, ['seeding' => 'random']);

        $seeds = $tournament->teams()->get()->pluck('pivot.seed')->sort()->values()->all();
        $this->assertSame(range(1, 7), $seeds);
    }

    #[Test]
    public function it_rejects_unknown_seeding_method(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);

        $this->postJson("/api/tournaments/{$tournament->id}/bracket", ['seeding' => 'alphabetical'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seeding');
    }

    #[Test]
    public function it_requires_at_least_two_teams(): void
    {
        $tournament = $this->draftTournamentWithTeams(1);

        $this->postJson("/api/tournaments/{$tournament->id}/bracket")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('teams');
    }

    #[Test]
    public function it_cannot_generate_twice(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);

        $this->postJson("/api/tournaments/{$tournament->id}/bracket")
            ->assertConflict()
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function it_returns_404_when_bracket_is_not_generated(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);

        $this->getJson("/api/tournaments/{$tournament->id}/bracket")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Bracket has not been generated yet.']);
    }

    #[Test]
    public function it_shows_bracket_grouped_by_rounds(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);

        $this->getJson("/api/tournaments/{$tournament->id}/bracket")
            ->assertOk()
            ->assertJsonPath('data.tournament_id', $tournament->id)
            ->assertJsonPath('data.size', 4)
            ->assertJsonCount(2, 'data.rounds')
            ->assertJsonCount(2, 'data.rounds.0.matches')
            ->assertJsonPath('data.rounds.0.name', 'Semifinal')
            ->assertJsonPath('data.rounds.1.matches.0.status', 'pending');
    }

    #[Test]
    public function it_resets_an_unplayed_bracket(): void
    {
        $tournament = $this->draftTournamentWithTeams(6);
        $this->generateBracket($tournament);

        $this->deleteJson("/api/tournaments/{$tournament->id}/bracket")->assertNoContent();

        $this->assertSame(0, $tournament->matches()->count());
        $this->assertSame(TournamentStatus::Draft, $tournament->refresh()->status);
    }

    #[Test]
    public function it_refuses_to_reset_after_a_match_is_played(): void
    {
        $tournament = $this->draftTournamentWithTeams(4);
        $this->generateBracket($tournament);
        $match = $this->matchAt($tournament, 1, 1);
        $this->putJson("/api/matches/{$match->id}/result", ['team1_score' => 1, 'team2_score' => 0])->assertOk();

        $this->deleteJson("/api/tournaments/{$tournament->id}/bracket")->assertConflict();

        $this->assertSame(3, $tournament->matches()->count());
    }

    #[Test]
    public function it_lists_tournament_matches_with_filters(): void
    {
        $tournament = $this->draftTournamentWithTeams(6);
        $this->generateBracket($tournament);

        $this->getJson("/api/tournaments/{$tournament->id}/matches?round=1&status=bye")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }
}
