<?php

namespace App\Http\Requests\Team;

use App\Http\Requests\PaginatedRequest;

class ListTeamsRequest extends PaginatedRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'size:2', 'alpha'],
            ...$this->paginationRules(),
        ];
    }
}
