<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tournament\ListTournamentsRequest;
use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use App\Services\TournamentService;

class TournamentController extends Controller
{
    public function __construct(
        private readonly TournamentService $tournaments,
    ) {}

    public function index(ListTournamentsRequest $request)
    {
        $tournaments = Tournament::query()
            ->withCount('teams')
            ->with('champion')
            ->when($request->status(), fn ($q, $status) => $q->withStatus($status))
            ->when($request->filled('game'), fn ($q) => $q->forGame($request->string('game')->toString()))
            ->latest('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return TournamentResource::collection($tournaments);
    }

    public function store(StoreTournamentRequest $request)
    {
        $tournament = $this->tournaments->createTournament($request->validated());

        return (new TournamentResource($tournament->loadCount('teams')->load('champion')))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tournaments.show', $tournament));
    }

    public function show(Tournament $tournament)
    {
        return new TournamentResource($tournament->loadCount('teams')->load('champion'));
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament)
    {
        $tournament = $this->tournaments->updateTournament($tournament, $request->validated());

        return new TournamentResource($tournament->loadCount('teams')->load('champion'));
    }

    public function destroy(Tournament $tournament)
    {
        $this->tournaments->deleteTournament($tournament);

        return response()->noContent();
    }
}
