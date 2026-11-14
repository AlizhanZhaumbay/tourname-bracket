<?php

namespace App\Services;

use App\Enums\TournamentStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function createTeam(array $attributes): Team
    {
        return Team::query()->create($attributes);
    }

    public function updateTeam(Team $team, array $attributes): Team
    {
        $team->update($attributes);

        return $team->refresh();
    }

    public function deleteTeam(Team $team): void
    {
        DB::transaction(function () use ($team) {
            $hasActiveRegistrations = $team->tournaments()
                ->whereIn('tournaments.status', [TournamentStatus::Draft, TournamentStatus::InProgress])
                ->lockForUpdate()
                ->exists();

            if ($hasActiveRegistrations) {
                throw new DomainConflictException('Team is registered in a draft or in-progress tournament.');
            }

            $team->tournaments()->detach();
            $team->delete();
        });
    }
}
