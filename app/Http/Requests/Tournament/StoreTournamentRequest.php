<?php

namespace App\Http\Requests\Tournament;

use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'game' => ['required', 'string', 'max:50'],
            'max_teams' => ['required', 'integer', Rule::in(Tournament::ALLOWED_SIZES)],
            'starts_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_teams.in' => 'The max teams must be one of: '.implode(', ', Tournament::ALLOWED_SIZES).'.',
        ];
    }
}
