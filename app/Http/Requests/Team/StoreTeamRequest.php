<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(array_map(
            fn ($value) => is_string($value) ? strtoupper(trim($value)) : $value,
            $this->only(['tag', 'country']),
        ));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:teams,name'],
            'tag' => ['required', 'string', 'between:2,5', 'regex:/^[A-Z0-9]+$/', 'unique:teams,tag'],
            'country' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'tag.regex' => 'The tag may only contain uppercase latin letters and digits.',
            'country.regex' => 'The country must be an ISO 3166-1 alpha-2 code.',
        ];
    }
}
