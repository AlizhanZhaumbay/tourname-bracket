<?php

namespace App\Http\Controllers;

use App\Http\Requests\Match\ListTournamentMatchesRequest;
use App\Http\Resources\MatchResource;
use App\Models\Tournament;

class TournamentMatchController extends Controller
{
    public function index(ListTournamentMatchesRequest $request, Tournament $tournament)
    {
        $matches = $tournament->matches()
            ->with(['team1', 'team2', 'winner'])
            ->when($request->round(), fn ($q, int $round) => $q->inRound($round))
            ->when($request->status(), fn ($q, $status) => $q->withStatus($status))
            ->orderBy('round')
            ->orderBy('position')
            ->paginate($request->perPage())
            ->withQueryString();

        return MatchResource::collection($matches);
    }
}
