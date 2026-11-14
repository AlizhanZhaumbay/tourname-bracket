<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlayerRequest extends FormRequest
{
    public function rules(): array
    {
        $player = $this->route('player');

        return [
            'team_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'nickname' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('players', 'nickname')->ignore($player)],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'role' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }
}
