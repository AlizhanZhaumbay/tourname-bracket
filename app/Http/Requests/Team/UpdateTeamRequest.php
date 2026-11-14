<?php

namespace App\Http\Requests\Team;

use Illuminate\Validation\Rule;

class UpdateTeamRequest extends StoreTeamRequest
{
    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('teams', 'name')->ignore($team)],
            'tag' => ['sometimes', 'required', 'string', 'between:2,5', 'regex:/^[A-Z0-9]+$/', Rule::unique('teams', 'tag')->ignore($team)],
            'country' => ['sometimes', 'nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
        ];
    }
}
