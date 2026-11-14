<?php

namespace App\Http\Requests\Tournament;

use App\Models\Player;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterTeamRequest extends FormRequest
{
    public function rules(): array
    {
        $tournament = $this->route('tournament');

        return [
            'team_id' => ['bail', 'required', 'integer', 'exists:teams,id', function (string $attribute, mixed $value, Closure $fail) {
                if (! Player::query()->where('team_id', $value)->exists()) {
                    $fail('The team must have at least one player to be registered.');
                }
            }],
            'seed' => [
                'nullable',
                'integer',
                'between:1,'.$tournament->max_teams,
                Rule::unique('tournament_team', 'seed')->where('tournament_id', $tournament->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'seed.unique' => 'This seed is already taken in the tournament.',
        ];
    }

    public function teamId(): int
    {
        return $this->integer('team_id');
    }

    public function seed(): ?int
    {
        return $this->filled('seed') ? $this->integer('seed') : null;
    }
}
