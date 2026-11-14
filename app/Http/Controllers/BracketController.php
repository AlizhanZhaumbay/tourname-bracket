<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tournament\GenerateBracketRequest;
use App\Http\Resources\BracketResource;
use App\Models\Tournament;
use App\Services\BracketGenerator;

class BracketController extends Controller
{
    public function __construct(
        private readonly BracketGenerator $generator,
    ) {}

    public function store(GenerateBracketRequest $request, Tournament $tournament)
    {
        $tournament = $this->generator->generateBracket($tournament, $request->seeding());

        return (new BracketResource($this->loadBracket($tournament)))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tournaments.bracket.show', $tournament));
    }

    public function show(Tournament $tournament)
    {
        $tournament = $this->loadBracket($tournament);

        abort_if($tournament->matches->isEmpty(), 404, 'Bracket has not been generated yet.');

        return new BracketResource($tournament);
    }

    public function destroy(Tournament $tournament)
    {
        $this->generator->resetBracket($tournament);

        return response()->noContent();
    }

    private function loadBracket(Tournament $tournament): Tournament
    {
        return $tournament->load([
            'champion',
            'matches' => fn ($q) => $q->with(['team1', 'team2', 'winner'])->orderBy('round')->orderBy('position'),
        ]);
    }
}
