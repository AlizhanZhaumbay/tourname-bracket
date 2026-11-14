<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\SeedingMethod;
use App\Enums\TournamentStatus;
use App\Exceptions\DomainConflictException;
use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Bracket\SeedOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BracketGenerator
{
    public function __construct(
        private readonly SeedOrder $seedOrder,
    ) {}

    public function generateBracket(Tournament $tournament, SeedingMethod $seeding): Tournament
    {
        return DB::transaction(function () use ($tournament, $seeding) {
            $tournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);

            if ($tournament->status !== TournamentStatus::Draft) {
                throw new DomainConflictException('Bracket can only be generated for a draft tournament.');
            }

            $teams = $tournament->teams()
                ->orderBy('tournament_team.created_at')
                ->orderBy('tournament_team.id')
                ->get();

            if ($teams->count() < 2) {
                throw ValidationException::withMessages(['teams' => 'At least 2 registered teams are required to generate a bracket.']);
            }

            $teamsBySeed = $this->assignSeeds($tournament, $teams, $seeding);
            $size = $this->seedOrder->bracketSize(count($teamsBySeed));
            $matches = $this->createMatches($tournament, (int) log($size, 2));
            $this->placeTeams($matches[1], $teamsBySeed, $size);

            $tournament->update(['status' => TournamentStatus::InProgress]);

            return $tournament;
        });
    }

    public function resetBracket(Tournament $tournament): Tournament
    {
        return DB::transaction(function () use ($tournament) {
            $tournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);

            if (! $tournament->matches()->exists()) {
                throw new DomainConflictException('Bracket has not been generated yet.');
            }

            if ($tournament->matches()->where('status', MatchStatus::Completed)->exists()) {
                throw new DomainConflictException('Cannot reset the bracket: at least one match has already been played.');
            }

            $tournament->matches()->delete();
            $tournament->update([
                'status' => TournamentStatus::Draft,
                'champion_team_id' => null,
            ]);

            return $tournament;
        });
    }

    private function assignSeeds(Tournament $tournament, Collection $teams, SeedingMethod $seeding): array
    {
        if ($seeding === SeedingMethod::Manual) {
            $seeds = $teams->pluck('pivot.seed')->filter()->sort()->values()->all();

            if ($seeds !== range(1, $teams->count())) {
                throw ValidationException::withMessages(['seeding' => 'Manual seeding requires every registered team to have a unique seed from 1 to '.$teams->count().' without gaps.']);
            }

            return $teams->mapWithKeys(fn (Team $team) => [$team->pivot->seed => $team->id])->all();
        }

        $ordered = $seeding === SeedingMethod::Random ? $teams->shuffle() : $teams;

        $tournament->teams()->newPivotQuery()->update(['seed' => null]);

        $teamsBySeed = [];

        foreach ($ordered->values() as $index => $team) {
            $seed = $index + 1;
            $tournament->teams()->updateExistingPivot($team->id, ['seed' => $seed]);
            $teamsBySeed[$seed] = $team->id;
        }

        return $teamsBySeed;
    }

    private function createMatches(Tournament $tournament, int $totalRounds): array
    {
        $matches = [];

        for ($round = 1; $round <= $totalRounds; $round++) {
            for ($position = 1; $position <= 2 ** ($totalRounds - $round); $position++) {
                $matches[$round][$position] = $tournament->matches()->create([
                    'round' => $round,
                    'position' => $position,
                    'status' => MatchStatus::Pending,
                ]);
            }
        }

        for ($round = 1; $round < $totalRounds; $round++) {
            foreach ($matches[$round] as $position => $match) {
                $match->update([
                    'next_match_id' => $matches[$round + 1][(int) ceil($position / 2)]->id,
                    'next_match_slot' => $position % 2 === 1 ? 1 : 2,
                ]);
            }
        }

        return $matches;
    }

    private function placeTeams(array $firstRound, array $teamsBySeed, int $size): void
    {
        $nextMatches = GameMatch::query()
            ->whereKey(array_filter(array_map(fn (GameMatch $m) => $m->next_match_id, $firstRound)))
            ->get()
            ->keyBy('id');

        foreach (array_chunk($this->seedOrder->forSize($size), 2) as $index => [$seed1, $seed2]) {
            $match = $firstRound[$index + 1];
            $team1 = $teamsBySeed[$seed1] ?? null;
            $team2 = $teamsBySeed[$seed2] ?? null;

            $match->fill(['team1_id' => $team1, 'team2_id' => $team2]);

            if ($team1 !== null && $team2 !== null) {
                $match->status = MatchStatus::Ready;
                $match->save();

                continue;
            }

            $match->fill(['winner_id' => $team1, 'status' => MatchStatus::Bye])->save();

            if ($next = $nextMatches->get($match->next_match_id)) {
                $this->placeTeam($next, $match->next_match_slot, $team1);
            }
        }
    }

    private function placeTeam(GameMatch $next, int $slot, ?int $teamId): void
    {
        $next->setAttribute($slot === 1 ? 'team1_id' : 'team2_id', $teamId);
        $next->status = $next->team1_id !== null && $next->team2_id !== null
            ? MatchStatus::Ready
            : MatchStatus::Pending;
        $next->save();
    }
}
