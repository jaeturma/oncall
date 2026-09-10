<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateJobStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof Job && ($this->user()?->can('transition', $job) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(JobStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('status')) {
                return;
            }

            $targetStatus = JobStatus::tryFrom((string) $this->input('status'));

            if ($targetStatus === null || ! in_array($targetStatus, $this->route('job')->allowedTransitionsFor($this->user()), true)) {
                $validator->errors()->add('status', 'That job status transition is not allowed.');
            }
        }];
    }
}
