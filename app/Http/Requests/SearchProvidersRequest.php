<?php

namespace App\Http\Requests;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SearchProvidersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'help' => ['required', 'string', 'regex:/^(service|category):[1-9][0-9]*$/'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->where('active', true)],
            'municipality_id' => ['nullable', 'integer', Rule::exists('municipalities', 'id')->where('province_id', $this->integer('province_id'))],
            'available_only' => ['nullable', 'boolean'],
            'min_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sort' => ['nullable', 'string', 'in:recommended,rating,nearest'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('help')) {
                return;
            }

            [$type, $id] = explode(':', $this->string('help')->value(), 2);
            $exists = $type === 'service'
                ? Service::whereKey($id)->where('active', true)->exists()
                : ServiceCategory::whereKey($id)->where('active', true)->exists();

            if (! $exists) {
                $validator->errors()->add('help', 'The selected service or category is unavailable.');
            }
        }];
    }
}
