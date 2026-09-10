<?php

namespace App\Http\Requests;

use App\Enums\ServiceUrgency;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Rules\NoDirectContact;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreServiceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ServiceRequest::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('active', true)],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'municipality_id' => ['nullable', 'integer', Rule::exists('municipalities', 'id')->where('province_id', $this->integer('province_id'))],
            'title' => ['required', 'string', 'max:160', new NoDirectContact],
            'description' => ['nullable', 'string', 'max:3000', new NoDirectContact],
            'urgency' => ['required', Rule::enum(ServiceUrgency::class)],
            'needed_at' => ['nullable', Rule::requiredIf($this->input('urgency') === ServiceUrgency::Scheduled->value), 'date', 'after:now'],
            'budget_min' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'safety_acknowledged' => ['required', 'accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['service_id', 'province_id'])) {
                $service = Service::find($this->integer('service_id'));
                $providerProfile = $this->route('provider_profile');

                if ($service === null || ! $providerProfile->canReceiveServiceRequest($service)) {
                    $validator->errors()->add('service_id', 'The selected provider is not eligible for this service.');
                }

                if ($providerProfile->province_id !== $this->integer('province_id')) {
                    $validator->errors()->add('province_id', 'The request must be within the provider service area.');
                }
            }

            if (is_numeric($this->input('budget_min')) && is_numeric($this->input('budget_max')) && (float) $this->input('budget_max') < (float) $this->input('budget_min')) {
                $validator->errors()->add('budget_max', 'The maximum budget must be at least the minimum budget.');
            }
        }];
    }
}
