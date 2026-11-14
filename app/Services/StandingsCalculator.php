<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class StandingsCalculator
{
    public function calculateStandings(Tournament $tournament): Collection
    {
        $tournament->loadMissing('teams');

        $played = $tournament->matches()
            ->where('status', MatchStatus::Completed)
            ->get();

        $totalRounds = (int) $tournament->matches()->max('round');

        return $tournament->teams
            ->map(fn (Team $team) => $this->buildRow($tournament, $team, $played, $totalRounds))
            ->sort(fn (array $a, array $b) => [(int) $a['placement'], $b['score_diff'], $a['team']->name]
                <=> [(int) $b['placement'], $a['score_diff'], $b['team']->name])
            ->values();
    }

    private function buildRow(Tournament $tournament, Team $team, Collection $played, int $totalRounds): array
    {
        $matches = $played->filter(fn (GameMatch $m) => $m->team1_id === $team->id || $m->team2_id === $team->id);

        $wins = $matches->where('winner_id', $team->id)->count();
        $lostIn = $matches->first(fn (GameMatch $m) => $m->winner_id !== $team->id);

        $scoreFor = $matches->sum(fn (GameMatch $m) => $m->team1_id === $team->id ? $m->team1_score : $m->team2_score);
        $scoreAgainst = $matches->sum(fn (GameMatch $m) => $m->team1_id === $team->id ? $m->team2_score : $m->team1_score);

        return [
            'team' => $team,
            'placement' => match (true) {
                $tournament->champion_team_id === $team->id => 1,
                $lostIn !== null => $this->placementForLoss($lostIn->round, $totalRounds),
                default => null,
            },
            'eliminated' => $lostIn !== null,
            'played' => $matches->count(),
            'wins' => $wins,
            'losses' => $matches->count() - $wins,
            'score_for' => (int) $scoreFor,
            'score_against' => (int) $scoreAgainst,
            'score_diff' => (int) $scoreFor - (int) $scoreAgainst,
        ];
    }

    private function placementForLoss(int $round, int $totalRounds): int|string
    {
        if ($round === $totalRounds) {
            return 2;
        }

        $best = 2 ** ($totalRounds - $round) + 1;

        return $best.'-'.(2 ** ($totalRounds - $round + 1));
    }
}
