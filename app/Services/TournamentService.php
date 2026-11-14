<?php

namespace App\Services;

use App\Enums\TournamentStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TournamentService
{
    public function createTournament(array $attributes): Tournament
    {
        return Tournament::query()->create([
            ...$attributes,
            'status' => TournamentStatus::Draft,
        ]);
    }

    public function updateTournament(Tournament $tournament, array $attributes): Tournament
    {
        return DB::transaction(function () use ($tournament, $attributes) {
            $tournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);

            if (isset($attributes['max_teams']) && (int) $attributes['max_teams'] !== $tournament->max_teams) {
                $this->assertMaxTeamsCanChange($tournament, (int) $attributes['max_teams']);
            }

            $tournament->update($attributes);

            return $tournament->refresh();
        });
    }

    public function deleteTournament(Tournament $tournament): void
    {
        if ($tournament->status === TournamentStatus::InProgress) {
            throw new DomainConflictException('Cannot delete a tournament that is in progress.');
        }

        $tournament->delete();
    }

    private function assertMaxTeamsCanChange(Tournament $tournament, int $maxTeams): void
    {
        if ($tournament->status !== TournamentStatus::Draft) {
            throw new DomainConflictException('Max teams can only be changed while the tournament is in draft.');
        }

        $registered = $tournament->teams()->count();

        if ($maxTeams < $registered) {
            throw ValidationException::withMessages(['max_teams' => "The max teams may not be less than the number of registered teams ({$registered})."]);
        }

        if ($tournament->teams()->wherePivot('seed', '>', $maxTeams)->exists()) {
            throw ValidationException::withMessages(['max_teams' => 'Some registered teams have seeds greater than the new max teams.']);
        }
    }
}
