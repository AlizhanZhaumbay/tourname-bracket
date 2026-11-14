<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public const array JSON_STRUCTURE = ['id', 'name', 'tag', 'country', 'created_at', 'updated_at'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tag' => $this->tag,
            'country' => $this->country,
            'seed' => $this->whenPivotLoaded('tournament_team', fn () => $this->pivot->seed),
            'players_count' => $this->whenCounted('players'),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
