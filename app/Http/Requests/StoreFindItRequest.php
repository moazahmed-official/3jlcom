<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFindItRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            
            // Vehicle criteria
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => [
                'nullable',
                'integer',
                'exists:models,id',
                // Model must belong to the selected brand
                Rule::exists('models', 'id')->where(function ($query) {
                    if ($this->brand_id) {
                        $query->where('brand_id', $this->brand_id);
                    }
                }),
            ],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            
            // Price range
            'min_price' => ['nullable', 'numeric', 'min:0', 'lte:max_price'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            
            // Year range
            'min_year' => ['nullable', 'integer', 'min:1900', 'max:2099', 'lte:max_year'],
            'max_year' => ['nullable', 'integer', 'min:1900', 'max:2099', 'gte:min_year'],
            
            // Mileage range
            'min_mileage' => ['nullable', 'integer', 'min:0', 'lte:max_mileage'],
            'max_mileage' => ['nullable', 'integer', 'min:0', 'gte:min_mileage'],
            
            // Location
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            
            // Additional filters
            'transmission' => ['nullable', Rule::in(['automatic', 'manual', 'cvt', 'semi-automatic'])],
            'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'electric', 'hybrid', 'lpg', 'cng'])],
            'body_type' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'condition' => ['nullable', Rule::in(['new', 'excellent', 'very_good', 'good', 'fair', 'poor', 'certified'])],
            'condition_rating' => ['nullable', 'integer', 'min:0', 'max:100'],
            
            // Expiration
            'expires_at' => ['nullable', 'date', 'after:now'],
            
            // Media attachments (reference images)
            'media' => ['nullable', 'array', 'max:5'],
            'media.*' => ['integer', 'exists:media,id'],
            
            // Auto-activate on creation
            'auto_activate' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your search request.',
            'title.max' => 'The title cannot exceed 255 characters.',
            'description.max' => 'The description cannot exceed 5000 characters.',
            'brand_id.exists' => 'The selected brand does not exist.',
            'model_id.exists' => 'The selected model does not exist or does not belong to the selected brand.',
            'category_id.exists' => 'The selected category does not exist.',
            'min_price.min' => 'Minimum price cannot be negative.',
            'min_price.lte' => 'Minimum price must be less than or equal to maximum price.',
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',
            'min_year.min' => 'Minimum year must be at least 1900.',
            'min_year.max' => 'Minimum year cannot exceed 2099.',
            'min_year.lte' => 'Minimum year must be less than or equal to maximum year.',
            'max_year.gte' => 'Maximum year must be greater than or equal to minimum year.',
            'min_mileage.min' => 'Minimum mileage cannot be negative.',
            'min_mileage.lte' => 'Minimum mileage must be less than or equal to maximum mileage.',
            'max_mileage.gte' => 'Maximum mileage must be greater than or equal to minimum mileage.',
            'city_id.exists' => 'The selected city does not exist.',
            'country_id.exists' => 'The selected country does not exist.',
            'transmission.in' => 'Invalid transmission type.',
            'fuel_type.in' => 'Invalid fuel type.',
            'condition.in' => 'Invalid condition value. Allowed: new, excellent, very_good, good, fair, poor, certified.',
            'condition_rating.min' => 'Condition rating must be at least 0.',
            'condition_rating.max' => 'Condition rating cannot exceed 100.',
            'expires_at.after' => 'Expiration date must be in the future.',
            'media.max' => 'You can attach up to 5 reference images.',
            'media.*.exists' => 'One of the selected media files does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default expiration to 30 days if not provided
        if (!$this->has('expires_at')) {
            $this->merge([
                'expires_at' => now()->addDays(30)->toDateTimeString(),
            ]);
        }

        // Convert string booleans
        if ($this->has('auto_activate')) {
            $this->merge([
                'auto_activate' => filter_var($this->auto_activate, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brand_id' => 'brand',
            'model_id' => 'model',
            'category_id' => 'category',
            'city_id' => 'city',
            'country_id' => 'country',
            'min_price' => 'minimum price',
            'max_price' => 'maximum price',
            'min_year' => 'minimum year',
            'max_year' => 'maximum year',
            'min_mileage' => 'minimum mileage',
            'max_mileage' => 'maximum mileage',
            'expires_at' => 'expiration date',
        ];
    }
}
