<?php

namespace App\Http\Controllers;

use App\Http\Requests\Player\ListPlayersRequest;
use App\Http\Requests\Player\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use App\Services\PlayerService;

class PlayerController extends Controller
{
    public function __construct(
        private readonly PlayerService $players,
    ) {}

    public function index(ListPlayersRequest $request)
    {
        $players = Player::query()
            ->with('team')
            ->when($request->teamId(), fn ($q, int $teamId) => $q->where('team_id', $teamId))
            ->when($request->freeAgentsOnly(), fn ($q) => $q->freeAgents())
            ->orderBy('nickname')
            ->paginate($request->perPage())
            ->withQueryString();

        return PlayerResource::collection($players);
    }

    public function show(Player $player)
    {
        return new PlayerResource($player->load('team'));
    }

    public function update(UpdatePlayerRequest $request, Player $player)
    {
        $player = $this->players->updatePlayer($player, $request->validated());

        return new PlayerResource($player->load('team'));
    }

    public function destroy(Player $player)
    {
        $this->players->deletePlayer($player);

        return response()->noContent();
    }
}
