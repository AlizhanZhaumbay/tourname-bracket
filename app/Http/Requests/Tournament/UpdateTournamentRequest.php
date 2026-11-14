<?php

namespace App\Http\Requests\Tournament;

use App\Models\Tournament;
use Illuminate\Validation\Rule;

class UpdateTournamentRequest extends StoreTournamentRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'game' => ['sometimes', 'required', 'string', 'max:50'],
            'max_teams' => ['sometimes', 'required', 'integer', Rule::in(Tournament::ALLOWED_SIZES)],
            'starts_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
