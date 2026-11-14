<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginatedRequest;
use App\Http\Requests\Player\StorePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Team;
use App\Services\PlayerService;

class TeamPlayerController extends Controller
{
    public function __construct(
        private readonly PlayerService $players,
    ) {}

    public function index(PaginatedRequest $request, Team $team)
    {
        $players = $team->players()
            ->orderBy('nickname')
            ->paginate($request->perPage())
            ->withQueryString();

        return PlayerResource::collection($players);
    }

    public function store(StorePlayerRequest $request, Team $team)
    {
        $player = $this->players->createPlayer([...$request->validated(), 'team_id' => $team->id]);

        return (new PlayerResource($player->load('team')))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('players.show', $player));
    }
}
