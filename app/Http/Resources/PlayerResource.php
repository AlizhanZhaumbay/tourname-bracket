<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    public const array JSON_STRUCTURE = ['id', 'team_id', 'nickname', 'full_name', 'role', 'is_free_agent', 'created_at', 'updated_at'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'nickname' => $this->nickname,
            'full_name' => $this->full_name,
            'role' => $this->role,
            'is_free_agent' => $this->team_id === null,
            'team' => new TeamResource($this->whenLoaded('team')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
