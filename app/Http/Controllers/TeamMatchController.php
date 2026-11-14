<?php

namespace App\Http\Controllers;

use App\Http\Requests\Match\ListTeamMatchesRequest;
use App\Http\Resources\MatchResource;
use App\Models\GameMatch;
use App\Models\Team;

class TeamMatchController extends Controller
{
    public function index(ListTeamMatchesRequest $request, Team $team)
    {
        $matches = GameMatch::query()
            ->involvingTeam($team->id)
            ->with(['team1', 'team2', 'winner'])
            ->when($request->tournamentId(), fn ($q, int $id) => $q->where('tournament_id', $id))
            ->when($request->status(), fn ($q, $status) => $q->withStatus($status))
            ->orderByDesc('tournament_id')
            ->orderBy('round')
            ->paginate($request->perPage())
            ->withQueryString();

        return MatchResource::collection($matches);
    }
}
