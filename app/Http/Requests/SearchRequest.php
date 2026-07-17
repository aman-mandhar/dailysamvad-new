<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:200']];
    }

    public function queryText(): string
    {
        return trim((string) $this->validated('q', ''));
    }
}
