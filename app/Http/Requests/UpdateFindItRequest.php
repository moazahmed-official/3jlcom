<?php

namespace App\Http\Requests;

use App\Models\FinditRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFindItRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $finditRequest = $this->route('findit_ad');
        
        // Must be authenticated
        if (!auth()->check()) {
            return false;
        }

        // If the route parameter is an ID, fetch the model
        if (!$finditRequest instanceof FinditRequest) {
            $finditRequest = FinditRequest::find($finditRequest);
        }

        if (!$finditRequest) {
            return false;
        }

        // Owner can always update
        if ($finditRequest->user_id === auth()->id()) {
            return true;
        }

        // Admin can update any request
        return auth()->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            
            // Vehicle criteria
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => [
                'nullable',
                'integer',
                'exists:models,id',
                // Model must belong to the selected brand
                Rule::exists('models', 'id')->where(function ($query) {
                    $brandId = $this->brand_id ?? $this->getFinditRequest()?->brand_id;
                    if ($brandId) {
                        $query->where('brand_id', $brandId);
                    }
                }),
            ],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            
            // Price range
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            
            // Year range
            'min_year' => ['nullable', 'integer', 'min:1900', 'max:2099'],
            'max_year' => ['nullable', 'integer', 'min:1900', 'max:2099'],
            
            // Mileage range
            'min_mileage' => ['nullable', 'integer', 'min:0'],
            'max_mileage' => ['nullable', 'integer', 'min:0'],
            
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
            
            // Expiration (only if still draft or active)
            'expires_at' => ['nullable', 'date', 'after:now'],
            
            // Status management
            'status' => ['sometimes', Rule::in(['draft', 'active', 'closed'])],
            
            // Media attachments (reference images)
            'media' => ['nullable', 'array', 'max:5'],
            'media.*' => ['integer', 'exists:media,id'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate price range
            $minPrice = $this->min_price ?? $this->getFinditRequest()?->min_price;
            $maxPrice = $this->max_price ?? $this->getFinditRequest()?->max_price;
            
            if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
                $validator->errors()->add('min_price', 'Minimum price must be less than or equal to maximum price.');
            }

            // Validate year range
            $minYear = $this->min_year ?? $this->getFinditRequest()?->min_year;
            $maxYear = $this->max_year ?? $this->getFinditRequest()?->max_year;
            
            if ($minYear !== null && $maxYear !== null && $minYear > $maxYear) {
                $validator->errors()->add('min_year', 'Minimum year must be less than or equal to maximum year.');
            }

            // Validate mileage range
            $minMileage = $this->min_mileage ?? $this->getFinditRequest()?->min_mileage;
            $maxMileage = $this->max_mileage ?? $this->getFinditRequest()?->max_mileage;
            
            if ($minMileage !== null && $maxMileage !== null && $minMileage > $maxMileage) {
                $validator->errors()->add('min_mileage', 'Minimum mileage must be less than or equal to maximum mileage.');
            }

            // Cannot change status of expired request
            if ($this->has('status') && $this->getFinditRequest()?->isExpired()) {
                $validator->errors()->add('status', 'Cannot change status of an expired request.');
            }
        });
    }

    /**
     * Get the FindIt request being updated.
     */
    protected function getFinditRequest(): ?FinditRequest
    {
        $finditRequest = $this->route('findit_ad');
        
        if ($finditRequest instanceof FinditRequest) {
            return $finditRequest;
        }
        
        return FinditRequest::find($finditRequest);
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
            'city_id.exists' => 'The selected city does not exist.',
            'country_id.exists' => 'The selected country does not exist.',
            'transmission.in' => 'Invalid transmission type.',
            'fuel_type.in' => 'Invalid fuel type.',
            'condition.in' => 'Invalid condition value. Allowed: new, excellent, very_good, good, fair, poor, certified.',
            'condition_rating.min' => 'Condition rating must be at least 0.',
            'condition_rating.max' => 'Condition rating cannot exceed 100.',
            'status.in' => 'Invalid status value. Allowed: draft, active, closed.',
            'expires_at.after' => 'Expiration date must be in the future.',
            'media.max' => 'You can attach up to 5 reference images.',
            'media.*.exists' => 'One of the selected media files does not exist.',
        ];
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
