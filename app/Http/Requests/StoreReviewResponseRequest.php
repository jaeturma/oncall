<?php

namespace App\Http\Requests;

use App\Models\Review;
use App\Models\ReviewSetting;
use App\Rules\NoDirectContact;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review && ($this->user()?->can('respond', $review) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'max:'.ReviewSetting::current()->response_max_length, new NoDirectContact],
        ];
    }
}
