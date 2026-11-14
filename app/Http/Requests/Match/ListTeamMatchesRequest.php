<?php

namespace App\Http\Requests\Match;

use App\Enums\MatchStatus;
use App\Http\Requests\PaginatedRequest;
use Illuminate\Validation\Rule;

class ListTeamMatchesRequest extends PaginatedRequest
{
    public function rules(): array
    {
        return [
            'tournament_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', Rule::enum(MatchStatus::class)],
            ...$this->paginationRules(),
        ];
    }

    public function tournamentId(): ?int
    {
        return $this->filled('tournament_id') ? $this->integer('tournament_id') : null;
    }

    public function status(): ?MatchStatus
    {
        return $this->enum('status', MatchStatus::class);
    }
}
