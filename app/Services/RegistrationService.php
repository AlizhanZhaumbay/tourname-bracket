<?php

namespace App\Services;

use App\Enums\TournamentStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function registerTeam(Tournament $tournament, Team $team, ?int $seed): Team
    {
        return DB::transaction(function () use ($tournament, $team, $seed) {
            $tournament = $this->lockOpenTournament($tournament);

            if ($tournament->teams()->whereKey($team->id)->exists()) {
                throw new DomainConflictException('Team is already registered in this tournament.');
            }

            if ($tournament->teams()->count() >= $tournament->max_teams) {
                throw new DomainConflictException("Tournament is full: maximum of {$tournament->max_teams} teams reached.");
            }

            $tournament->teams()->attach($team->id, ['seed' => $seed]);

            return $tournament->teams()->whereKey($team->id)->withCount('players')->firstOrFail();
        });
    }

    public function withdrawTeam(Tournament $tournament, Team $team): void
    {
        DB::transaction(function () use ($tournament, $team) {
            $tournament = $this->lockOpenTournament($tournament);

            if ($tournament->teams()->detach($team->id) === 0) {
                throw (new ModelNotFoundException)->setModel(Team::class, [$team->id]);
            }
        });
    }

    private function lockOpenTournament(Tournament $tournament): Tournament
    {
        $tournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);

        if ($tournament->status !== TournamentStatus::Draft) {
            throw new DomainConflictException(match ($tournament->status) {
                TournamentStatus::InProgress => 'Registration is closed: bracket already generated.',
                TournamentStatus::Completed => 'Registration is closed: tournament is completed.',
            });
        }

        return $tournament;
    }
}
