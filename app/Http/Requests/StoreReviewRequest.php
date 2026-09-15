<?php

namespace App\Http\Requests;

use App\Models\Job;
use App\Models\Review;
use App\Models\ReviewSetting;
use App\Rules\NoDirectContact;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof Job && ($this->user()?->can('create', [Review::class, $job]) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settings = ReviewSetting::current();

        return [
            // Integer 1-5 already rejects 0, 6, decimals, and negatives.
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => [$settings->comment_required ? 'required' : 'nullable', 'string', 'max:'.$settings->max_comment_length, new NoDirectContact],
        ];
    }
}
