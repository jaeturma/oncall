<?php

namespace App\Http\Requests;

use App\Enums\ReportCategory;
use App\Models\Job;
use App\Models\UserReport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof Job && ($this->user()?->can('view', $job) ?? false) && ($this->user()?->can('create', UserReport::class) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ReportCategory::class)],
            'description' => ['required', 'string', 'max:3000'],
        ];
    }
}
