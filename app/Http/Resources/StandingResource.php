<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StandingResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'team' => ['id', 'name', 'tag'], 'placement', 'eliminated', 'played', 'wins', 'losses',
        'score_for', 'score_against', 'score_diff',
    ];

    public function toArray(Request $request): array
    {
        return [
            ...$this->resource,
            'team' => new TeamResource($this->resource['team']),
        ];
    }
}
