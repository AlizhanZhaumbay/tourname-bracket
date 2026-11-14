<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\ListTeamsRequest;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Services\TeamService;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teams,
    ) {}

    public function index(ListTeamsRequest $request)
    {
        $teams = Team::query()
            ->withCount('players')
            ->when($request->filled('search'), fn ($q) => $q->search($request->string('search')->toString()))
            ->when($request->filled('country'), fn ($q) => $q->fromCountry($request->string('country')->toString()))
            ->orderBy('name')
            ->paginate($request->perPage())
            ->withQueryString();

        return TeamResource::collection($teams);
    }

    public function store(StoreTeamRequest $request)
    {
        $team = $this->teams->createTeam($request->validated());

        return (new TeamResource($team))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('teams.show', $team));
    }

    public function show(Team $team)
    {
        return new TeamResource($team->load('players'));
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team = $this->teams->updateTeam($team, $request->validated());

        return new TeamResource($team->load('players'));
    }

    public function destroy(Team $team)
    {
        $this->teams->deleteTeam($team);

        return response()->noContent();
    }
}
