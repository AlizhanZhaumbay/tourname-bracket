<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginatedRequest;
use App\Http\Requests\Tournament\RegisterTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\RegistrationService;

class TournamentTeamController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrations,
    ) {}

    public function index(PaginatedRequest $request, Tournament $tournament)
    {
        $teams = $tournament->teams()
            ->withCount('players')
            ->orderByRaw('tournament_team.seed asc nulls last')
            ->orderBy('tournament_team.id')
            ->paginate($request->perPage())
            ->withQueryString();

        return TeamResource::collection($teams);
    }

    public function store(RegisterTeamRequest $request, Tournament $tournament)
    {
        $team = Team::query()->findOrFail($request->teamId());
        $team = $this->registrations->registerTeam($tournament, $team, $request->seed());

        return (new TeamResource($team))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tournaments.teams.index', $tournament));
    }

    public function destroy(Tournament $tournament, Team $team)
    {
        $this->registrations->withdrawTeam($tournament, $team);

        return response()->noContent();
    }
}
