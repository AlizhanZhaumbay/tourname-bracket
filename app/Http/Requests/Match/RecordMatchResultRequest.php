<?php

namespace App\Http\Requests\Match;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class RecordMatchResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'team1_score' => ['required', 'integer', 'between:0,999'],
            'team2_score' => [
                'required',
                'integer',
                'between:0,999',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (is_numeric($this->input('team1_score')) && (int) $value === (int) $this->input('team1_score')) {
                        $fail('A draw is not allowed: one team must win.');
                    }
                },
            ],
        ];
    }
}
