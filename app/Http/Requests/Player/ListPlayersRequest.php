<?php

namespace App\Http\Requests\Player;

use App\Http\Requests\PaginatedRequest;

class ListPlayersRequest extends PaginatedRequest
{
    public function rules(): array
    {
        return [
            'team_id' => ['sometimes', 'integer'],
            'free_agent' => ['sometimes', 'in:true,false,1,0'],
            ...$this->paginationRules(),
        ];
    }

    public function teamId(): ?int
    {
        return $this->filled('team_id') ? $this->integer('team_id') : null;
    }

    public function freeAgentsOnly(): bool
    {
        return $this->boolean('free_agent');
    }
}
