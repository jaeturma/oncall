<?php

namespace App\Http\Requests;

use App\Enums\JobMessageType;
use App\Models\Job;
use App\Models\JobMessage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof Job && ($this->user()?->can('create', [JobMessage::class, $job]) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(JobMessageType::class)],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
