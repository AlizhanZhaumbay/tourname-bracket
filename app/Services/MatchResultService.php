<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\TournamentStatus;
use App\Exceptions\DomainConflictException;
use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

class MatchResultService
{
    public function recordResult(GameMatch $match, int $team1Score, int $team2Score): GameMatch
    {
        return DB::transaction(function () use ($match, $team1Score, $team2Score) {
            $match = GameMatch::query()->lockForUpdate()->findOrFail($match->id);

            match ($match->status) {
                MatchStatus::Pending => throw new DomainConflictException('Match is not ready: both opponents are not known yet.'),
                MatchStatus::Bye => throw new DomainConflictException('Cannot record a result for a bye match.'),
                MatchStatus::Ready, MatchStatus::Completed => null,
            };

            $next = $match->next_match_id
                ? GameMatch::query()->lockForUpdate()->findOrFail($match->next_match_id)
                : null;

            if ($match->status === MatchStatus::Completed && $next?->status === MatchStatus::Completed) {
                throw new DomainConflictException('Cannot change the result: the next match has already been played.');
            }

            $winnerId = $team1Score > $team2Score ? $match->team1_id : $match->team2_id;

            $match->fill([
                'team1_score' => $team1Score,
                'team2_score' => $team2Score,
                'winner_id' => $winnerId,
                'status' => MatchStatus::Completed,
                'completed_at' => $match->completed_at ?? now(),
            ])->save();

            if ($next !== null) {
                $this->placeTeam($next, $match->next_match_slot, $winnerId);
            } else {
                $this->crownChampion($match->tournament_id, $winnerId);
            }

            return $match;
        });
    }

    private function crownChampion(int $tournamentId, int $winnerId): void
    {
        Tournament::query()->lockForUpdate()->findOrFail($tournamentId)->update([
            'status' => TournamentStatus::Completed,
            'champion_team_id' => $winnerId,
        ]);
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
