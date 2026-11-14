<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginatedRequest extends FormRequest
{
    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }

    protected function paginationRules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
