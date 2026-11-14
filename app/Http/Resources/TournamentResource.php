<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    public const array JSON_STRUCTURE = ['id', 'name', 'game', 'max_teams', 'status', 'starts_at', 'created_at', 'updated_at'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'game' => $this->game,
            'max_teams' => $this->max_teams,
            'status' => $this->status,
            'teams_count' => $this->whenCounted('teams'),
            'champion_team_id' => $this->champion_team_id,
            'champion' => new TeamResource($this->whenLoaded('champion')),
            'starts_at' => $this->starts_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
