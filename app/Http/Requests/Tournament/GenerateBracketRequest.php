<?php

namespace App\Http\Requests\Tournament;

use App\Enums\SeedingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateBracketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'seeding' => ['sometimes', 'nullable', Rule::enum(SeedingMethod::class)],
        ];
    }

    public function seeding(): SeedingMethod
    {
        return $this->enum('seeding', SeedingMethod::class) ?? SeedingMethod::RegistrationOrder;
    }
}
