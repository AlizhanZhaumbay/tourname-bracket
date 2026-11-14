<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'id', 'tournament_id', 'round', 'position', 'team1_id', 'team2_id', 'team1_score', 'team2_score',
        'winner_id', 'next_match_id', 'next_match_slot', 'status', 'scheduled_at', 'completed_at',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tournament_id' => $this->tournament_id,
            'round' => $this->round,
            'position' => $this->position,
            'team1_id' => $this->team1_id,
            'team2_id' => $this->team2_id,
            'team1' => new TeamResource($this->whenLoaded('team1')),
            'team2' => new TeamResource($this->whenLoaded('team2')),
            'team1_score' => $this->team1_score,
            'team2_score' => $this->team2_score,
            'winner_id' => $this->winner_id,
            'winner' => new TeamResource($this->whenLoaded('winner')),
            'next_match_id' => $this->next_match_id,
            'next_match_slot' => $this->next_match_slot,
            'next_match' => $this->whenLoaded('nextMatch', fn () => $this->nextMatch ? [
                'id' => $this->nextMatch->id,
                'round' => $this->nextMatch->round,
                'position' => $this->nextMatch->position,
                'status' => $this->nextMatch->status,
            ] : null),
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
