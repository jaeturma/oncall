<?php

namespace App\Http\Requests;

use App\Enums\DisputeCategory;
use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Dispute::class, $this->route('job')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', new Enum(DisputeCategory::class)],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
        ];
    }
}
