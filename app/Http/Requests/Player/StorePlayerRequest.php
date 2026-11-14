<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:30', 'unique:players,nickname'],
            'full_name' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:30'],
        ];
    }
}
