<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateMatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'scheduled_at' => ['present', 'nullable', 'date'],
        ];
    }

    public function scheduledAt(): ?Carbon
    {
        return $this->date('scheduled_at');
    }
}
