<?php

namespace App\Http\Resources;

use App\Services\Bracket\SeedOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BracketResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'tournament_id', 'status', 'size', 'rounds' => [['round', 'name', 'matches']],
    ];

    public function toArray(Request $request): array
    {
        $totalRounds = (int) $this->matches->max('round');

        return [
            'tournament_id' => $this->id,
            'status' => $this->status,
            'size' => 2 ** $totalRounds,
            'champion' => new TeamResource($this->whenLoaded('champion')),
            'rounds' => $this->matches
                ->groupBy('round')
                ->map(fn ($matches, int $round) => [
                    'round' => $round,
                    'name' => SeedOrder::roundName($round, $totalRounds),
                    'matches' => MatchResource::collection($matches->sortBy('position')->values()),
                ])
                ->sortKeys()
                ->values(),
        ];
    }
}
