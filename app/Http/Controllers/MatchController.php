<?php

namespace App\Http\Controllers;

use App\Http\Requests\Match\RecordMatchResultRequest;
use App\Http\Requests\Match\UpdateMatchRequest;
use App\Http\Resources\MatchResource;
use App\Models\GameMatch;
use App\Services\MatchResultService;
use App\Services\MatchService;

class MatchController extends Controller
{
    private const array RELATIONS = ['team1', 'team2', 'winner', 'nextMatch'];

    public function __construct(
        private readonly MatchService $matches,
        private readonly MatchResultService $results,
    ) {}

    public function show(GameMatch $match)
    {
        return new MatchResource($match->load(self::RELATIONS));
    }

    public function update(UpdateMatchRequest $request, GameMatch $match)
    {
        $match = $this->matches->scheduleMatch($match, $request->scheduledAt());

        return new MatchResource($match->load(self::RELATIONS));
    }

    public function result(RecordMatchResultRequest $request, GameMatch $match)
    {
        $match = $this->results->recordResult($match, $request->integer('team1_score'), $request->integer('team2_score'));

        return new MatchResource($match->load(self::RELATIONS));
    }
}
