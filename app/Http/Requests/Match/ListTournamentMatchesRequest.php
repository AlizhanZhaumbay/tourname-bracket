<?php

namespace App\Http\Requests\Match;

use App\Enums\MatchStatus;
use App\Http\Requests\PaginatedRequest;
use Illuminate\Validation\Rule;

class ListTournamentMatchesRequest extends PaginatedRequest
{
    public function rules(): array
    {
        return [
            'round' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::enum(MatchStatus::class)],
            ...$this->paginationRules(),
        ];
    }

    public function round(): ?int
    {
        return $this->filled('round') ? $this->integer('round') : null;
    }

    public function status(): ?MatchStatus
    {
        return $this->enum('status', MatchStatus::class);
    }
}
