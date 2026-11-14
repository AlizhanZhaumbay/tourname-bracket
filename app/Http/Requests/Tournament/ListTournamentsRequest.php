<?php

namespace App\Http\Requests\Tournament;

use App\Enums\TournamentStatus;
use App\Http\Requests\PaginatedRequest;
use Illuminate\Validation\Rule;

class ListTournamentsRequest extends PaginatedRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TournamentStatus::class)],
            'game' => ['sometimes', 'string', 'max:50'],
            ...$this->paginationRules(),
        ];
    }

    public function status(): ?TournamentStatus
    {
        return $this->enum('status', TournamentStatus::class);
    }
}
