<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AcceptServiceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $serviceRequest = $this->route('service_request');

        return $serviceRequest instanceof ServiceRequest && ($this->user()?->can('respond', $serviceRequest) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agreed_price' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ];
    }
}
