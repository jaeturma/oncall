<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexProviderReviewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Whitelisted sort values only — never an arbitrary client sort
            // field (Phase P §34).
            'sort' => ['nullable', 'string', 'in:newest,highest,lowest'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
