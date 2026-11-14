<?php

namespace App\Http\Controllers;

use App\Http\Resources\StandingResource;
use App\Models\Tournament;
use App\Services\StandingsCalculator;

class StandingsController extends Controller
{
    public function __construct(
        private readonly StandingsCalculator $standings,
    ) {}

    public function __invoke(Tournament $tournament)
    {
        return StandingResource::collection($this->standings->calculateStandings($tournament));
    }
}
